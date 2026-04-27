<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;

class CsatController extends Controller
{
    /** Configuración + dashboard. */
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('csat');
        $this->requireCan('csat.view');

        $type = (string)$this->input('type', 'csat');
        if (!in_array($type, ['csat','nps'], true)) $type = 'csat';

        $settings = $this->db->one('SELECT * FROM csat_settings WHERE tenant_id=? AND type=?', [$tenant->id, $type])
            ?: ['tenant_id'=>$tenant->id,'type'=>$type,'is_enabled'=>0,'delay_minutes'=>60,'subject'=>'¿Cómo fue tu experiencia?','intro'=>null,'thanks_message'=>null];

        $surveys = $this->db->all(
            "SELECT s.*, t.code AS ticket_code, t.subject AS ticket_subject
             FROM csat_surveys s LEFT JOIN tickets t ON t.id = s.ticket_id
             WHERE s.tenant_id = ? AND s.type = ? AND s.responded_at IS NOT NULL
             ORDER BY s.responded_at DESC LIMIT 60",
            [$tenant->id, $type]
        );

        $stats = $this->stats($tenant->id, $type);

        $this->render('csat/index', [
            'title' => $type === 'nps' ? 'NPS' : 'CSAT',
            'type' => $type,
            'settings' => $settings,
            'surveys' => $surveys,
            'stats' => $stats,
        ]);
    }

    public function settings(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('csat');
        $this->requireCan('csat.config');
        $this->validateCsrf();

        $type = (string)$this->input('type', 'csat');
        if (!in_array($type, ['csat','nps'], true)) $type = 'csat';
        $exists = $this->db->one('SELECT * FROM csat_settings WHERE tenant_id=? AND type=?', [$tenant->id, $type]);

        $data = [
            'is_enabled'     => (int)($this->input('is_enabled') ? 1 : 0),
            'delay_minutes'  => max(0, (int)$this->input('delay_minutes', 60)),
            'subject'        => trim((string)$this->input('subject', '¿Cómo fue tu experiencia?')),
            'intro'          => (string)$this->input('intro', '') ?: null,
            'thanks_message' => (string)$this->input('thanks_message', '') ?: null,
        ];

        if ($exists) {
            $this->db->update('csat_settings', $data, 'tenant_id=? AND type=?', [$tenant->id, $type]);
        } else {
            $this->db->insert('csat_settings', array_merge(['tenant_id'=>$tenant->id,'type'=>$type], $data));
        }
        $this->session->flash('success', 'Ajustes guardados.');
        $this->redirect('/t/' . $tenant->slug . '/csat?type=' . $type);
    }

    /** Vista pública de la encuesta (cliente). */
    public function show(array $params): void
    {
        $token = (string)$params['token'];
        $survey = $this->db->one(
            "SELECT s.*, t.subject AS ticket_subject, t.code AS ticket_code, ten.name AS tenant_name, ten.slug AS tenant_slug
             FROM csat_surveys s
             JOIN tickets t ON t.id = s.ticket_id
             JOIN tenants ten ON ten.id = s.tenant_id
             WHERE s.token = ? LIMIT 1",
            [$token]
        );
        if (!$survey) { http_response_code(404); echo 'Encuesta no encontrada.'; return; }

        $cfg = $this->db->one('SELECT * FROM csat_settings WHERE tenant_id=? AND type=?', [(int)$survey['tenant_id'], $survey['type']]);

        $this->render('csat/public', [
            'title' => $survey['type'] === 'nps' ? 'NPS' : 'CSAT',
            'survey' => $survey,
            'cfg' => $cfg,
        ], 'public');
    }

    /** Guardar respuesta del cliente. */
    public function respond(array $params): void
    {
        $token = (string)$params['token'];
        $survey = $this->db->one('SELECT * FROM csat_surveys WHERE token=? LIMIT 1', [$token]);
        if (!$survey) { http_response_code(404); echo 'Encuesta no encontrada.'; return; }
        if ($survey['responded_at']) {
            $this->session->flash('info', 'Ya respondiste esta encuesta. ¡Gracias!');
            $this->redirect('/csat/' . $token);
        }

        $score = (int)$this->input('score', 0);
        $max = $survey['type'] === 'nps' ? 10 : 5;
        if ($score < 0) $score = 0;
        if ($score > $max) $score = $max;

        $this->db->update('csat_surveys', [
            'score' => $score,
            'comment' => trim((string)$this->input('comment', '')) ?: null,
            'responded_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => (int)$survey['id']]);

        // Sincronizar el score al ticket si es CSAT (1-5)
        if ($survey['type'] === 'csat') {
            try {
                $this->db->update('tickets', ['satisfaction_rating' => $score], 'id=? AND tenant_id=?', [(int)$survey['ticket_id'], (int)$survey['tenant_id']]);
            } catch (\Throwable $e) {}
        }

        $this->session->flash('success', '¡Gracias por tu feedback!');
        $this->redirect('/csat/' . $token);
    }

    /** Disparar manualmente para un ticket. */
    public function trigger(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('csat');
        $this->requireCan('csat.config');
        $this->validateCsrf();

        $ticketId = (int)$this->input('ticket_id', 0);
        $type = (string)$this->input('type', 'csat');
        if (!in_array($type, ['csat','nps'], true)) $type = 'csat';

        $ticket = $this->db->one('SELECT * FROM tickets WHERE id=? AND tenant_id=?', [$ticketId, $tenant->id]);
        if (!$ticket || empty($ticket['requester_email'])) {
            $this->session->flash('error', 'Ticket sin email de solicitante.');
            $this->redirect('/t/' . $tenant->slug . '/tickets/' . $ticketId);
        }
        self::createAndSend((int)$tenant->id, (int)$ticket['id'], $type);
        $this->session->flash('success', 'Encuesta enviada al solicitante.');
        $this->redirect('/t/' . $tenant->slug . '/tickets/' . $ticketId);
    }

    /** API estática reutilizable: crea encuesta + envía email. */
    public static function createAndSend(int $tenantId, int $ticketId, string $type = 'csat'): ?int
    {
        $app = \App\Core\Application::get();
        $db = $app->db;
        $existing = $db->one('SELECT id FROM csat_surveys WHERE tenant_id=? AND ticket_id=? AND type=? AND responded_at IS NULL', [$tenantId, $ticketId, $type]);
        if ($existing) return (int)$existing['id'];

        $ticket = $db->one('SELECT t.*, ten.name AS tenant_name, ten.slug AS tenant_slug FROM tickets t JOIN tenants ten ON ten.id = t.tenant_id WHERE t.id=? AND t.tenant_id=?', [$ticketId, $tenantId]);
        if (!$ticket || empty($ticket['requester_email'])) return null;

        $token = bin2hex(random_bytes(16));
        $sid = $db->insert('csat_surveys', [
            'tenant_id' => $tenantId,
            'ticket_id' => $ticketId,
            'type'      => $type,
            'token'     => $token,
            'sent_at'   => date('Y-m-d H:i:s'),
        ]);
        $cfg = $db->one('SELECT * FROM csat_settings WHERE tenant_id=? AND type=?', [$tenantId, $type]);
        $appUrl = rtrim($app->config['app']['url'] ?? '', '/');
        $url = $appUrl . '/csat/' . $token;
        $subject = $cfg['subject'] ?? '¿Cómo fue tu experiencia?';
        $intro = $cfg['intro'] ?? 'Tu feedback nos ayuda a mejorar.';
        $inner = '<p>Hola,</p>'
            . '<p>Resolvimos tu solicitud <strong>' . htmlspecialchars($ticket['subject']) . '</strong> (' . htmlspecialchars($ticket['code']) . ').</p>'
            . '<p>' . htmlspecialchars($intro) . '</p>'
            . '<p>Solo te tomará 30 segundos.</p>';
        try {
            (new Mailer())->send(
                ['email' => $ticket['requester_email'], 'name' => $ticket['requester_name'] ?? 'Cliente'],
                $subject . ' · ' . $ticket['tenant_name'],
                Mailer::template($subject, $inner, 'Calificar ahora', $url)
            );
        } catch (\Throwable $e) { /* swallow */ }
        return (int)$sid;
    }

    protected function stats(int $tenantId, string $type): array
    {
        $base = "FROM csat_surveys WHERE tenant_id = ? AND type = ?";
        $args = [$tenantId, $type];
        $total = (int)$this->db->val("SELECT COUNT(*) $base", $args);
        $responded = (int)$this->db->val("SELECT COUNT(*) $base AND responded_at IS NOT NULL", $args);
        $avg = (float)$this->db->val("SELECT IFNULL(AVG(score), 0) $base AND score IS NOT NULL", $args);

        if ($type === 'nps') {
            $promoters = (int)$this->db->val("SELECT COUNT(*) $base AND score >= 9", $args);
            $detractors = (int)$this->db->val("SELECT COUNT(*) $base AND score <= 6 AND score IS NOT NULL", $args);
            $passives = (int)$this->db->val("SELECT COUNT(*) $base AND score BETWEEN 7 AND 8", $args);
            $score = $responded > 0 ? round((($promoters - $detractors) / $responded) * 100) : 0;
            return compact('total','responded','avg','promoters','detractors','passives','score');
        }
        // CSAT
        $satisfied = (int)$this->db->val("SELECT COUNT(*) $base AND score >= 4", $args);
        $unsatisfied = (int)$this->db->val("SELECT COUNT(*) $base AND score <= 2 AND score IS NOT NULL", $args);
        $rate = $responded > 0 ? round(($satisfied / $responded) * 100) : 0;
        return compact('total','responded','avg','satisfied','unsatisfied','rate');
    }
}
