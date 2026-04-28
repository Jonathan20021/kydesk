<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\MeetingAi;
use App\Core\Plan;
use App\Core\Tenant;

/**
 * Endpoints de IA para el módulo de Reuniones.
 *
 * Panel (autenticado · ai_assist + meetings):
 *   POST /t/{slug}/meetings/{id}/ai/analyze   — re-analiza la reserva (intent/sentiment/summary/topics)
 *   POST /t/{slug}/meetings/{id}/ai/briefing  — genera briefing pre-meeting con preguntas sugeridas
 *   POST /t/{slug}/meetings/{id}/ai/followup  — genera email de follow-up post-meeting
 *
 * Público (sin auth · gated por meeting_settings.ai_public_suggester):
 *   POST /book/{slug}/ai/suggest              — recomienda mejor tipo de reunión según descripción
 */
class MeetingAiController extends Controller
{
    /* ──────────────────────────────── PANEL ──────────────────────────────── */

    public function analyze(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireFeature('ai_assist');           // Enterprise-only (defense in depth)
        $this->requireCan('meetings.view');
        $this->validateCsrf();

        $meeting = $this->loadMeeting($tenant, (int)$params['id']);
        if (!$meeting) $this->json(['ok' => false, 'error' => 'No encontrada'], 404);

        $result = $this->runAnalysis($tenant, $meeting);
        if (!$result['ok']) $this->json($result, 400);

        $fresh = $this->db->one('SELECT ai_intent, ai_sentiment, ai_urgency, ai_summary, ai_topics, ai_processed_at FROM meetings WHERE id=?', [$meeting['id']]);
        $this->json([
            'ok'        => true,
            'intent'    => $fresh['ai_intent'],
            'sentiment' => $fresh['ai_sentiment'],
            'urgency'   => $fresh['ai_urgency'],
            'summary'   => $fresh['ai_summary'],
            'topics'    => $fresh['ai_topics'] ? json_decode($fresh['ai_topics'], true) : [],
            'at'        => $fresh['ai_processed_at'],
        ]);
    }

    public function briefing(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireFeature('ai_assist');           // Enterprise-only (defense in depth)
        $this->requireCan('meetings.view');
        $this->validateCsrf();

        $meeting = $this->loadMeeting($tenant, (int)$params['id']);
        if (!$meeting) $this->json(['ok' => false, 'error' => 'No encontrada'], 404);

        $context = $this->customerContext($tenant->id, $meeting);
        $prompt = [
            'system' => "Sos el asistente de un agente que está por entrar a una reunión con un cliente. Generá un briefing ejecutivo en español, en formato markdown, conciso (máximo 350 palabras) con estas secciones:\n\n## Resumen del cliente\n## Contexto de la solicitud\n## Preguntas sugeridas\n## Action items previos\n\nNo inventes información. Si no tenés datos para una sección, dejala explícitamente vacía o dí 'Sin información'.",
            'user'   => "Generá el briefing para esta reunión:\n\n" . $context,
        ];

        $result = MeetingAi::call($tenant, $prompt, [
            'action'     => 'meeting_briefing',
            'user_id'    => $this->auth->userId(),
            'max_tokens' => 1200,
            'temperature'=> 0.4,
        ]);
        if (!$result['ok']) $this->json($result, 400);

        $this->db->update('meetings', [
            'ai_briefing'    => $result['text'],
            'ai_briefing_at' => date('Y-m-d H:i:s'),
        ], 'id=? AND tenant_id=?', [$meeting['id'], $tenant->id]);

        $this->json([
            'ok'       => true,
            'briefing' => $result['text'],
            'at'       => date('Y-m-d H:i:s'),
            'tokens'   => ($result['tokens_in'] ?? 0) + ($result['tokens_out'] ?? 0),
        ]);
    }

    public function followup(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireFeature('ai_assist');           // Enterprise-only (defense in depth)
        $this->requireCan('meetings.view');
        $this->validateCsrf();

        $meeting = $this->loadMeeting($tenant, (int)$params['id']);
        if (!$meeting) $this->json(['ok' => false, 'error' => 'No encontrada'], 404);

        $hostNotes = trim((string)$this->input('host_notes', ''));
        $context = $this->customerContext($tenant->id, $meeting);
        if ($hostNotes !== '') {
            $context .= "\n\n## Notas del host (después de la reunión)\n" . $hostNotes;
        }

        $prompt = [
            'system' => "Sos un asistente que redacta emails de follow-up post-reunión. Devolvé SOLO el cuerpo del email (sin asunto), en español, tono profesional y cordial. Máximo 4 párrafos. Mencioná lo discutido si está en las notas y proponé los próximos pasos. NO uses placeholders como [Nombre] — usá los datos reales del contexto.",
            'user'   => "Redactá el email de follow-up:\n\n" . $context,
        ];

        $result = MeetingAi::call($tenant, $prompt, [
            'action'     => 'meeting_followup',
            'user_id'    => $this->auth->userId(),
            'max_tokens' => 800,
            'temperature'=> 0.6,
        ]);
        if (!$result['ok']) $this->json($result, 400);

        $this->db->update('meetings', ['ai_followup' => $result['text']], 'id=? AND tenant_id=?', [$meeting['id'], $tenant->id]);

        $this->json([
            'ok'    => true,
            'email' => $result['text'],
            'tokens'=> ($result['tokens_in'] ?? 0) + ($result['tokens_out'] ?? 0),
        ]);
    }

    /* ──────────────────────────────── PÚBLICO ──────────────────────────────── */

    public function suggest(array $params): void
    {
        $slug = (string)$params['slug'];
        $row = $this->db->one('SELECT * FROM meeting_settings WHERE public_slug=?', [$slug]);
        $tenant = null;
        if ($row) $tenant = Tenant::find((int)$row['tenant_id']);
        if (!$tenant) {
            $tenant = Tenant::resolve($slug);
            if ($tenant) $row = $this->db->one('SELECT * FROM meeting_settings WHERE tenant_id=?', [$tenant->id]);
        }
        if (!$tenant) $this->json(['ok' => false, 'error' => 'not_found'], 404);

        if (!Plan::has($tenant, 'meetings')) $this->json(['ok' => false, 'error' => 'meetings_disabled'], 404);
        // Enterprise gate explícito (defense in depth — guard() también lo valida)
        if (!Plan::has($tenant, 'ai_assist')) $this->json(['ok' => false, 'error' => 'enterprise_only'], 403);
        if ($row && (int)($row['ai_public_suggester'] ?? 1) === 0) {
            $this->json(['ok' => false, 'error' => 'suggester_disabled'], 403);
        }

        // Validar que la IA está disponible (cuota, asignación, key)
        $guard = MeetingAi::guard($tenant);
        if (!$guard['ok']) $this->json(['ok' => false, 'error' => 'ai_unavailable', 'detail' => $guard['error']], 503);

        $description = trim((string)$this->input('description', ''));
        if ($description === '') $this->json(['ok' => false, 'error' => 'empty'], 400);
        if (strlen($description) > 500) $description = substr($description, 0, 500);

        $types = $this->db->all(
            'SELECT slug, name, description, duration_minutes, location_type FROM meeting_types WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name',
            [$tenant->id]
        );
        if (empty($types)) $this->json(['ok' => false, 'error' => 'no_types'], 404);

        $catalog = "Tipos de reunión disponibles:\n";
        foreach ($types as $t) {
            $catalog .= "- slug: {$t['slug']} · nombre: {$t['name']} · duración: {$t['duration_minutes']} min · tipo: {$t['location_type']}\n";
            if (!empty($t['description'])) $catalog .= "  descripción: " . substr($t['description'], 0, 200) . "\n";
        }

        $prompt = [
            'system' => "Sos un asistente que recomienda el tipo de reunión más apropiado para un cliente. Te paso el catálogo de tipos disponibles y la descripción del cliente. Respondé SOLO con un JSON válido (sin markdown, sin texto adicional) con esta estructura: {\"slug\": \"<el slug exacto del tipo>\", \"reason\": \"<una frase breve, máximo 25 palabras, en español, dirigida al cliente, explicando por qué este tipo funciona>\", \"confidence\": \"high|medium|low\"}. Si ninguno encaja, devolvé el más cercano con confidence=low.",
            'user'   => $catalog . "\n\nDescripción del cliente: \"" . $description . "\"\n\nResponde con el JSON.",
        ];

        $result = MeetingAi::call($tenant, $prompt, [
            'action'     => 'meeting_suggest',
            'user_id'    => null,
            'max_tokens' => 200,
            'temperature'=> 0.2,
            'timeout'    => 12,
        ]);
        if (!$result['ok']) $this->json(['ok' => false, 'error' => 'ai_error'], 503);

        $parsed = MeetingAi::extractJson($result['text']);
        if (!$parsed || empty($parsed['slug'])) $this->json(['ok' => false, 'error' => 'parse_error'], 502);

        $match = null;
        foreach ($types as $t) {
            if ($t['slug'] === $parsed['slug']) { $match = $t; break; }
        }
        if (!$match) $this->json(['ok' => false, 'error' => 'unknown_type'], 502);

        $publicSlug = $row['public_slug'] ?? $tenant->slug;
        $base = rtrim((string)($this->app->config['app']['url'] ?? ''), '/');
        $this->json([
            'ok'         => true,
            'slug'       => $match['slug'],
            'name'       => $match['name'],
            'duration'   => (int)$match['duration_minutes'],
            'reason'     => substr((string)($parsed['reason'] ?? ''), 0, 240),
            'confidence' => in_array($parsed['confidence'] ?? '', ['high','medium','low'], true) ? $parsed['confidence'] : 'medium',
            'url'        => '/book/' . rawurlencode($publicSlug) . '/' . rawurlencode($match['slug']),
        ]);
    }

    /* ──────────────────────────────── HELPERS ──────────────────────────────── */

    /**
     * Análisis automático ejecutado al confirmar una reserva pública.
     * Llamado desde BookingController::store después del insert.
     * Best-effort: si falla, no bloquea la reserva.
     */
    public static function analyzeAfterBooking(Tenant $tenant, int $meetingId): void
    {
        try {
            $db = \App\Core\Application::get()->db;
            $meeting = $db->one(
                "SELECT m.*, mt.name AS type_name, mt.duration_minutes AS type_duration
                 FROM meetings m LEFT JOIN meeting_types mt ON mt.id = m.meeting_type_id
                 WHERE m.id = ? AND m.tenant_id = ?",
                [$meetingId, $tenant->id]
            );
            if (!$meeting) return;
            (new self())->runAnalysis($tenant, $meeting);
        } catch (\Throwable $e) { /* best-effort */ }
    }

    protected function loadMeeting(Tenant $tenant, int $id): ?array
    {
        return $this->db->one(
            "SELECT m.*, mt.name AS type_name, mt.duration_minutes AS type_duration, mt.description AS type_description,
                    mt.location_type AS type_location_type,
                    u.name AS host_name, u.email AS host_email,
                    c.name AS company_name, c.industry AS company_industry, c.size AS company_size,
                    c.tier AS company_tier, c.website AS company_website
             FROM meetings m
             LEFT JOIN meeting_types mt ON mt.id = m.meeting_type_id
             LEFT JOIN users u ON u.id = m.host_user_id
             LEFT JOIN companies c ON c.id = m.company_id
             WHERE m.id = ? AND m.tenant_id = ?",
            [$id, $tenant->id]
        );
    }

    protected function customerContext(int $tenantId, array $meeting): string
    {
        $ctx = "## Reunión\n";
        $ctx .= "- Tipo: " . ($meeting['type_name'] ?? '—') . " (" . (int)$meeting['duration_minutes'] . " min)\n";
        $ctx .= "- Cuándo: " . $meeting['scheduled_at'] . " (" . $meeting['timezone'] . ")\n";
        $ctx .= "- Host: " . ($meeting['host_name'] ?? '—') . "\n";
        $ctx .= "- Estado: " . $meeting['status'] . "\n";

        $ctx .= "\n## Cliente\n";
        $ctx .= "- Nombre: " . $meeting['customer_name'] . "\n";
        $ctx .= "- Email: " . $meeting['customer_email'] . "\n";
        if (!empty($meeting['customer_phone']))   $ctx .= "- Teléfono: " . $meeting['customer_phone'] . "\n";
        if (!empty($meeting['customer_company'])) $ctx .= "- Empresa (texto): " . $meeting['customer_company'] . "\n";
        if (!empty($meeting['company_name']))     $ctx .= "- Empresa CRM: " . $meeting['company_name'] . " · industria: " . ($meeting['company_industry'] ?? '—') . " · tier: " . ($meeting['company_tier'] ?? '—') . "\n";

        // Histórico previo de reuniones con este cliente
        $prev = $this->db->all(
            "SELECT scheduled_at, status, ai_intent, ai_summary FROM meetings
             WHERE tenant_id=? AND customer_email=? AND id<>?
             ORDER BY scheduled_at DESC LIMIT 5",
            [$tenantId, $meeting['customer_email'], $meeting['id']]
        );
        if (!empty($prev)) {
            $ctx .= "\n## Histórico de reuniones previas con este cliente (" . count($prev) . ")\n";
            foreach ($prev as $p) {
                $ctx .= "- " . substr($p['scheduled_at'], 0, 16) . " · " . $p['status'];
                if (!empty($p['ai_intent'])) $ctx .= " · intent: " . $p['ai_intent'];
                if (!empty($p['ai_summary'])) $ctx .= "\n  resumen: " . substr($p['ai_summary'], 0, 200);
                $ctx .= "\n";
            }
        }

        // Histórico de tickets si la empresa coincide
        if (!empty($meeting['company_id'])) {
            $tickets = $this->db->all(
                "SELECT subject, priority, status, created_at FROM tickets WHERE tenant_id=? AND company_id=? ORDER BY created_at DESC LIMIT 3",
                [$tenantId, (int)$meeting['company_id']]
            );
            if (!empty($tickets)) {
                $ctx .= "\n## Tickets recientes de la empresa\n";
                foreach ($tickets as $t) {
                    $ctx .= "- " . substr($t['created_at'], 0, 10) . " · " . $t['priority'] . " · " . $t['status'] . " · " . substr($t['subject'], 0, 100) . "\n";
                }
            }
        }

        if (!empty($meeting['notes'])) {
            $ctx .= "\n## Mensaje del cliente al reservar\n" . $meeting['notes'] . "\n";
        }
        if (!empty($meeting['custom_answers'])) {
            $answers = json_decode($meeting['custom_answers'], true);
            if (is_array($answers)) {
                $ctx .= "\n## Respuestas a preguntas personalizadas\n";
                foreach ($answers as $a) {
                    $ctx .= "- " . ($a['label'] ?? '?') . ": " . ($a['value'] ?? '') . "\n";
                }
            }
        }
        return $ctx;
    }

    /**
     * Corre el análisis estructurado del meeting (intent/sentiment/urgency/summary/topics).
     * Persiste en las columnas ai_*.
     * @return array{ok:bool, error?:string, parsed?:array}
     */
    protected function runAnalysis(Tenant $tenant, array $meeting): array
    {
        $context = $this->customerContext($tenant->id, $meeting);

        $prompt = [
            'system' => "Sos un asistente que analiza solicitudes de reuniones. Te paso el contexto y devolvés SOLO un JSON válido (sin markdown ni texto adicional) con esta estructura exacta:\n\n{\n  \"intent\": \"sales|support|demo|consultation|complaint|partnership|other\",\n  \"sentiment\": \"positive|neutral|negative\",\n  \"urgency\": \"low|medium|high\",\n  \"summary\": \"<resumen ejecutivo en 2-3 oraciones, en español, del propósito de la reunión>\",\n  \"topics\": [\"<tag corto>\", \"<tag corto>\"]\n}\n\nReglas:\n- topics: array de 1 a 4 tags muy cortos (1-3 palabras), en español, en minúsculas\n- Si no tenés información para inferir, usá: intent=other, sentiment=neutral, urgency=low\n- summary: NO inventes datos. Si solo hay nombre + tipo de reunión, decí \"Reserva sin contexto adicional\".",
            'user'   => "Analizá esta reunión:\n\n" . $context,
        ];

        $result = MeetingAi::call($tenant, $prompt, [
            'action'     => 'meeting_analyze',
            'user_id'    => $this->auth ? $this->auth->userId() : null,
            'max_tokens' => 400,
            'temperature'=> 0.2,
            'timeout'    => 15,
        ]);
        if (!$result['ok']) return $result;

        $parsed = MeetingAi::extractJson($result['text']);
        if (!$parsed) return ['ok' => false, 'error' => 'No se pudo parsear la respuesta IA'];

        $intent = $this->normalize($parsed['intent'] ?? null, ['sales','support','demo','consultation','complaint','partnership','other'], 'other');
        $sentiment = $this->normalize($parsed['sentiment'] ?? null, ['positive','neutral','negative'], 'neutral');
        $urgency = $this->normalize($parsed['urgency'] ?? null, ['low','medium','high'], 'low');
        $summary = trim((string)($parsed['summary'] ?? ''));
        if (mb_strlen($summary) > 600) $summary = mb_substr($summary, 0, 597) . '…';
        $topics = is_array($parsed['topics'] ?? null) ? array_slice(array_map(fn($x) => mb_substr(trim((string)$x), 0, 30), $parsed['topics']), 0, 4) : [];

        $this->db->update('meetings', [
            'ai_intent'        => $intent,
            'ai_sentiment'     => $sentiment,
            'ai_urgency'       => $urgency,
            'ai_summary'       => $summary ?: null,
            'ai_topics'        => $topics ? json_encode($topics, JSON_UNESCAPED_UNICODE) : null,
            'ai_processed_at'  => date('Y-m-d H:i:s'),
        ], 'id=? AND tenant_id=?', [$meeting['id'], $tenant->id]);

        return ['ok' => true, 'parsed' => $parsed];
    }

    protected function normalize($v, array $allowed, string $default): string
    {
        $v = strtolower(trim((string)$v));
        return in_array($v, $allowed, true) ? $v : $default;
    }
}
