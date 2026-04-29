<?php
namespace App\Controllers;

use App\Core\Application;
use App\Core\Controller;
use App\Core\MeetingAi;
use App\Core\Tenant;

/**
 * Receptor de webhooks de Jitsi as a Service (8x8.vc).
 *
 *   URL pública: POST /api/jaas/webhook/{slug}
 *
 * Eventos soportados (https://developer.8x8.com/jaas/docs/webhooks-data):
 *   - CONFERENCE_CREATED        → marca el meeting como en curso
 *   - CONFERENCE_ENDED          → cierra el meeting (status=completed) + duración
 *   - PARTICIPANT_JOINED        → registra en meeting_participants
 *   - PARTICIPANT_LEFT          → actualiza left_at + duration
 *   - RECORDING_UPLOADED        → guarda URL en meeting_recordings
 *   - TRANSCRIPTION_UPLOADED    → fetch + persist + dispara Kyros IA si está habilitado
 *   - CHAT_UPLOADED             → archiva chat
 *
 * Verificación:
 *   1) Si el tenant configuró jaas_webhook_secret, validamos un header X-Kydesk-Signature (HMAC-SHA256 del body).
 *   2) Si no hay secret configurado, aceptamos pero registramos signature_valid=0.
 *
 * Nota: JaaS no firma webhooks por default. Para signatura dura, los devs configuran un proxy
 * que firma con el secret antes de reenviar acá. Ese secret está autogenerado en migración.
 */
class JaasWebhookController extends Controller
{
    public function receive(array $params): void
    {
        $slug = (string)$params['slug'];
        $tenant = Tenant::resolve($slug);
        if (!$tenant) {
            // También probar contra public_slug
            $row = $this->db->one('SELECT tenant_id FROM meeting_settings WHERE public_slug=?', [$slug]);
            if ($row) $tenant = Tenant::find((int)$row['tenant_id']);
        }
        if (!$tenant) $this->json(['ok' => false, 'error' => 'tenant_not_found'], 404);

        $rawBody = file_get_contents('php://input') ?: '';
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) $this->json(['ok' => false, 'error' => 'invalid_json'], 400);

        // Verificación de firma (opcional)
        $sigHeader = $_SERVER['HTTP_X_KYDESK_SIGNATURE'] ?? '';
        $secret = (string)$this->db->val('SELECT jaas_webhook_secret FROM meeting_settings WHERE tenant_id=?', [$tenant->id]);
        $sigValid = false;
        if ($secret !== '' && $sigHeader !== '') {
            $expected = hash_hmac('sha256', $rawBody, $secret);
            $sigValid = hash_equals($expected, $sigHeader);
            if (!$sigValid) {
                $this->logEvent($tenant, $payload, false);
                $this->json(['ok' => false, 'error' => 'invalid_signature'], 401);
            }
        }

        $eventType = strtoupper((string)($payload['eventType'] ?? $payload['type'] ?? ''));
        $fqn = (string)($payload['fqn'] ?? $payload['data']['fqn'] ?? '');
        $meetingId = $this->resolveMeetingId($tenant, $payload);

        $this->logEvent($tenant, $payload, $sigValid, $meetingId);

        // Routing
        try {
            switch ($eventType) {
                case 'CONFERENCE_CREATED':   $this->handleConferenceCreated($tenant, $meetingId, $payload); break;
                case 'CONFERENCE_ENDED':     $this->handleConferenceEnded($tenant, $meetingId, $payload); break;
                case 'PARTICIPANT_JOINED':   $this->handleParticipantJoined($tenant, $meetingId, $payload); break;
                case 'PARTICIPANT_LEFT':     $this->handleParticipantLeft($tenant, $meetingId, $payload); break;
                case 'RECORDING_UPLOADED':
                case 'SIP_JIBRI_RECORDING_UPLOADED':
                    $this->handleRecordingUploaded($tenant, $meetingId, $payload, 'recording'); break;
                case 'TRANSCRIPTION_UPLOADED':
                    $this->handleTranscriptionUploaded($tenant, $meetingId, $payload); break;
                case 'CHAT_UPLOADED':
                    $this->handleRecordingUploaded($tenant, $meetingId, $payload, 'chat'); break;
            }
        } catch (\Throwable $e) {
            // No crashear el webhook si un handler falla
            error_log('[JaasWebhook] handler error: ' . $e->getMessage());
        }

        $this->json(['ok' => true, 'event' => $eventType, 'meeting_id' => $meetingId]);
    }

    /* ─────────────────────── Handlers ─────────────────────── */

    protected function handleConferenceCreated(Tenant $tenant, ?int $meetingId, array $payload): void
    {
        if (!$meetingId) return;
        $this->db->update('meetings', [
            'conference_started_at' => date('Y-m-d H:i:s'),
            'status'                => 'confirmed',
        ], 'id=? AND tenant_id=? AND conference_started_at IS NULL', [$meetingId, $tenant->id]);
    }

    protected function handleConferenceEnded(Tenant $tenant, ?int $meetingId, array $payload): void
    {
        if (!$meetingId) return;
        $endedAt = date('Y-m-d H:i:s');

        $update = [
            'conference_ended_at' => $endedAt,
        ];
        // Si estaba en confirmed y duró >= 1 min, marcar completed
        $row = $this->db->one('SELECT status, conference_started_at FROM meetings WHERE id=?', [$meetingId]);
        if ($row && in_array($row['status'], ['confirmed','scheduled'], true)) {
            $started = strtotime($row['conference_started_at'] ?? $endedAt);
            $duration = strtotime($endedAt) - $started;
            if ($duration >= 60) $update['status'] = 'completed';
        }
        $this->db->update('meetings', $update, 'id=? AND tenant_id=?', [$meetingId, $tenant->id]);

        // Cerrar participantes que no salieron explícitamente
        $this->db->run(
            "UPDATE meeting_participants SET left_at = ?, duration_seconds = TIMESTAMPDIFF(SECOND, joined_at, ?)
             WHERE tenant_id=? AND meeting_id=? AND left_at IS NULL",
            [$endedAt, $endedAt, $tenant->id, $meetingId]
        );
    }

    protected function handleParticipantJoined(Tenant $tenant, ?int $meetingId, array $payload): void
    {
        if (!$meetingId) return;
        $data = $payload['data'] ?? $payload;
        $participant = $data['participant'] ?? [];
        $jid = (string)($participant['id'] ?? $data['participantId'] ?? '');

        // Si ya existe el participante con joined_at sin left_at, no duplicar
        if ($jid !== '') {
            $existing = $this->db->val(
                "SELECT id FROM meeting_participants WHERE meeting_id=? AND participant_jid=? AND left_at IS NULL LIMIT 1",
                [$meetingId, $jid]
            );
            if ($existing) return;
        }

        $this->db->insert('meeting_participants', [
            'tenant_id'      => $tenant->id,
            'meeting_id'     => $meetingId,
            'participant_jid'=> $jid ?: null,
            'name'           => (string)($participant['name'] ?? '') ?: null,
            'email'          => (string)($participant['email'] ?? '') ?: null,
            'role'           => (string)($participant['role'] ?? '') ?: null,
            'is_moderator'   => !empty($participant['moderator']) ? 1 : 0,
            'joined_at'      => date('Y-m-d H:i:s'),
            'raw_event'      => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
        // Bump count
        $this->db->run("UPDATE meetings SET participants_count = (SELECT COUNT(DISTINCT IFNULL(email,participant_jid)) FROM meeting_participants WHERE meeting_id=?) WHERE id=?", [$meetingId, $meetingId]);
    }

    protected function handleParticipantLeft(Tenant $tenant, ?int $meetingId, array $payload): void
    {
        if (!$meetingId) return;
        $data = $payload['data'] ?? $payload;
        $participant = $data['participant'] ?? [];
        $jid = (string)($participant['id'] ?? $data['participantId'] ?? '');
        $leftAt = date('Y-m-d H:i:s');

        if ($jid === '') return;
        $this->db->run(
            "UPDATE meeting_participants
                SET left_at = ?, duration_seconds = TIMESTAMPDIFF(SECOND, joined_at, ?)
              WHERE tenant_id=? AND meeting_id=? AND participant_jid=? AND left_at IS NULL",
            [$leftAt, $leftAt, $tenant->id, $meetingId, $jid]
        );
    }

    protected function handleRecordingUploaded(Tenant $tenant, ?int $meetingId, array $payload, string $kind): void
    {
        if (!$meetingId) return;
        $data = $payload['data'] ?? $payload;
        $url  = (string)($data['url'] ?? $data['fileUrl'] ?? $data['recordingUrl'] ?? '');
        $fileId = (string)($data['fileId'] ?? $data['id'] ?? '');
        $duration = isset($data['duration']) ? (int)$data['duration'] : null;
        $size = isset($data['fileSize']) ? (int)$data['fileSize'] : null;
        $mime = (string)($data['mimeType'] ?? $data['contentType'] ?? '');

        if ($url === '' && $fileId === '') return;

        // Idempotencia: si ya tenemos este file_id, skip
        if ($fileId !== '') {
            $exists = $this->db->val('SELECT id FROM meeting_recordings WHERE meeting_id=? AND file_id=?', [$meetingId, $fileId]);
            if ($exists) return;
        }

        $this->db->insert('meeting_recordings', [
            'tenant_id'        => $tenant->id,
            'meeting_id'       => $meetingId,
            'kind'             => $kind,
            'file_url'         => $url ?: null,
            'file_id'          => $fileId ?: null,
            'duration_seconds' => $duration,
            'file_size_bytes'  => $size,
            'mime_type'        => $mime ?: null,
            'raw_event'        => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }

    protected function handleTranscriptionUploaded(Tenant $tenant, ?int $meetingId, array $payload): void
    {
        if (!$meetingId) return;
        $data = $payload['data'] ?? $payload;
        $url  = (string)($data['url'] ?? $data['fileUrl'] ?? '');
        $fileId = (string)($data['fileId'] ?? $data['id'] ?? '');

        // Persistir el evento como recording kind=transcription
        $this->db->insert('meeting_recordings', [
            'tenant_id'  => $tenant->id,
            'meeting_id' => $meetingId,
            'kind'       => 'transcription',
            'file_url'   => $url ?: null,
            'file_id'    => $fileId ?: null,
            'raw_event'  => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        // Fetch del transcript (si la URL es accesible)
        $transcriptText = '';
        if ($url !== '') {
            $ctx = stream_context_create(['http' => ['timeout' => 15]]);
            $body = @file_get_contents($url, false, $ctx);
            if ($body !== false) $transcriptText = (string)$body;
        }
        if ($transcriptText === '') return;

        $this->db->update('meeting_recordings', ['transcript_text' => mb_substr($transcriptText, 0, 60000)], 'meeting_id=? AND kind=? AND file_id=?', [$meetingId, 'transcription', $fileId ?: '']);
        $this->db->update('meetings', ['transcript_text' => mb_substr($transcriptText, 0, 60000)], 'id=? AND tenant_id=?', [$meetingId, $tenant->id]);

        // Pipeline IA: si Kyros IA está disponible y el tenant lo tiene activo, generar resumen + action items
        $shouldRunAi = (int)$this->db->val('SELECT transcript_ai_summary FROM meeting_settings WHERE tenant_id=?', [$tenant->id]) === 1;
        if ($shouldRunAi) $this->runTranscriptAi($tenant, $meetingId, $transcriptText);
    }

    /* ─────────────────────── IA Pipeline ─────────────────────── */

    protected function runTranscriptAi(Tenant $tenant, int $meetingId, string $transcript): void
    {
        $guard = MeetingAi::guard($tenant);
        if (!$guard['ok']) return;

        $clean = mb_substr(trim($transcript), 0, 30000);
        $prompt = [
            'system' => "Sos un asistente que analiza transcripciones de reuniones. Devolvé SOLO un JSON válido (sin markdown) con esta estructura:\n{\n  \"summary\": \"<resumen ejecutivo 4-6 oraciones en español>\",\n  \"action_items\": [\"<item corto 1>\", \"<item corto 2>\", ...]\n}\nReglas:\n- summary: claro, orientado a decisiones tomadas + temas discutidos\n- action_items: 1-8 elementos · cada uno máximo 80 caracteres · empezar con verbo en infinitivo\n- Si la transcripción no tiene acuerdos claros, devolvé action_items: []",
            'user'   => "Analizá esta transcripción:\n\n" . $clean,
        ];
        $result = MeetingAi::call($tenant, $prompt, [
            'action'     => 'meeting_transcript',
            'max_tokens' => 1200,
            'temperature'=> 0.3,
            'timeout'    => 30,
        ]);
        if (!$result['ok']) return;

        $parsed = MeetingAi::extractJson($result['text']);
        if (!$parsed) return;

        $summary = (string)($parsed['summary'] ?? '');
        $items = is_array($parsed['action_items'] ?? null) ? array_slice(array_filter(array_map(fn($x) => trim((string)$x), $parsed['action_items']), 'strlen'), 0, 12) : [];

        $this->db->update('meetings', [
            'transcript_summary'      => $summary ?: null,
            'transcript_action_items' => $items ? json_encode($items, JSON_UNESCAPED_UNICODE) : null,
        ], 'id=? AND tenant_id=?', [$meetingId, $tenant->id]);

        $this->db->run(
            "UPDATE meeting_recordings SET ai_processed=1, ai_summary=?, ai_action_items=?
              WHERE meeting_id=? AND kind='transcription'
              ORDER BY id DESC LIMIT 1",
            [$summary, $items ? json_encode($items, JSON_UNESCAPED_UNICODE) : null, $meetingId]
        );
    }

    /* ─────────────────────── Helpers ─────────────────────── */

    /**
     * Extrae el meeting_id del payload de JaaS.
     * JaaS envía el "fqn" (fully qualified name) que es "{appId}/{roomName}".
     * Buscamos por conference_room_id que matchee con el roomName extraído del fqn.
     */
    protected function resolveMeetingId(Tenant $tenant, array $payload): ?int
    {
        $data = $payload['data'] ?? $payload;
        $fqn  = (string)($data['fqn'] ?? $payload['fqn'] ?? '');
        $room = (string)($data['room'] ?? '');

        // De fqn extraemos lo que va después del /
        if ($fqn !== '' && strpos($fqn, '/') !== false) {
            $room = substr($fqn, strpos($fqn, '/') + 1);
        }
        if ($room === '') return null;

        $id = $this->db->val('SELECT id FROM meetings WHERE tenant_id=? AND conference_room_id=? LIMIT 1', [$tenant->id, $room]);
        return $id ? (int)$id : null;
    }

    protected function logEvent(Tenant $tenant, array $payload, bool $sigValid, ?int $meetingId = null): void
    {
        try {
            $this->db->insert('meeting_jaas_events', [
                'tenant_id'      => $tenant->id,
                'meeting_id'     => $meetingId,
                'event_type'     => substr((string)($payload['eventType'] ?? $payload['type'] ?? 'unknown'), 0, 80),
                'fqn'            => substr((string)($payload['fqn'] ?? $payload['data']['fqn'] ?? ''), 0, 255) ?: null,
                'payload'        => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'signature_valid'=> $sigValid ? 1 : 0,
            ]);
        } catch (\Throwable $e) { /* no bloquear */ }
    }
}
