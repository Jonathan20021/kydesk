<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Helpers;
use App\Core\Mailer;
use App\Core\Plan;
use App\Core\Tenant;

/**
 * Página pública estilo Calendly.
 *   /book/{slug}                        → portada con todos los tipos de reunión
 *   /book/{slug}/{typeSlug}             → selector de fecha y hora
 *   /book/{slug}/{typeSlug}/slots.json  → AJAX: slots disponibles para una fecha
 *   /book/{slug}/{typeSlug}/confirm     → POST: crear la reserva
 *   /book/{slug}/confirmation/{token}   → mensaje de éxito
 *   /book/{slug}/manage/{token}         → gestionar (cancelar / reprogramar)
 *
 * No requiere autenticación. Usa CSRF para los POST.
 */
class BookingController extends Controller
{
    /* ─────────────────────────────────────────────────────────
       Portada pública
       ───────────────────────────────────────────────────────── */
    public function index(array $params): void
    {
        [$tenant, $settings] = $this->resolveContext((string)$params['slug']);
        $this->guardEnabled($tenant, $settings);

        $types = $this->db->all(
            'SELECT * FROM meeting_types WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name',
            [$tenant->id]
        );

        // ¿Mostrar el suggester de IA? Requiere: setting on + IA disponible para el tenant
        $aiSuggester = false;
        if ((int)($settings['ai_public_suggester'] ?? 1) === 1) {
            $aiSuggester = \App\Core\MeetingAi::guard($tenant)['ok'];
        }

        $this->renderPublic('booking/index', [
            'title'       => $settings['page_title'] ?: ('Agenda una reunión con ' . $tenant->name),
            'tenant'      => $tenant,
            'settings'    => $settings,
            'types'       => $types,
            'aiSuggester' => $aiSuggester,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       Selector de fecha/hora para un tipo
       ───────────────────────────────────────────────────────── */
    public function show(array $params): void
    {
        [$tenant, $settings] = $this->resolveContext((string)$params['slug']);
        $this->guardEnabled($tenant, $settings);

        $type = $this->db->one(
            'SELECT * FROM meeting_types WHERE tenant_id=? AND slug=? AND is_active=1',
            [$tenant->id, (string)$params['type']]
        );
        if (!$type) {
            http_response_code(404);
            $this->renderPublic('booking/not_found', [
                'title' => 'Tipo no disponible',
                'tenant' => $tenant,
                'settings' => $settings,
            ]);
            return;
        }

        $questions = $this->parseQuestions($type['custom_questions']);

        // primera fecha disponible (mínimo aviso)
        $minHours = (int)$type['min_notice_hours'];
        $earliest = date('Y-m-d', strtotime('+' . $minHours . ' hours'));
        $latest   = date('Y-m-d', strtotime('+' . (int)$type['max_advance_days'] . ' days'));

        $this->renderPublic('booking/show', [
            'title'     => $type['name'] . ' · ' . $tenant->name,
            'tenant'    => $tenant,
            'settings'  => $settings,
            'type'      => $type,
            'questions' => $questions,
            'earliest'  => $earliest,
            'latest'    => $latest,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       AJAX: slots disponibles para una fecha
       ───────────────────────────────────────────────────────── */
    public function slots(array $params): void
    {
        [$tenant, $settings] = $this->resolveContext((string)$params['slug']);
        $this->guardEnabledJson($tenant, $settings);

        $type = $this->db->one(
            'SELECT * FROM meeting_types WHERE tenant_id=? AND slug=? AND is_active=1',
            [$tenant->id, (string)$params['type']]
        );
        if (!$type) $this->json(['ok' => false, 'error' => 'type_not_found'], 404);

        $date = (string)$this->input('date', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $this->json(['ok' => false, 'error' => 'invalid_date'], 400);

        $slots = $this->computeSlotsForDate($tenant, $type, $date);
        $this->json([
            'ok'       => true,
            'date'     => $date,
            'duration' => (int)$type['duration_minutes'],
            'timezone' => $settings['timezone'] ?? 'America/Santo_Domingo',
            'slots'    => $slots,
        ]);
    }

    /**
     * Calcula los slots disponibles para una fecha concreta restando:
     *   · franjas de availability del host
     *   · días bloqueados
     *   · reuniones existentes del host
     *   · ventanas de min_notice y max_advance
     *   · buffers before/after del tipo
     *
     * @return array<int, array{start:string,end:string,iso:string,label:string}>
     */
    public function computeSlotsForDate(Tenant $tenant, array $type, string $date): array
    {
        $duration = (int)$type['duration_minutes'];
        $bufferAfter  = (int)$type['buffer_after_minutes'];
        $bufferBefore = (int)$type['buffer_before_minutes'];
        $step         = max(5, (int)$type['slot_step_minutes']);
        $minHours     = (int)$type['min_notice_hours'];
        $maxDays      = (int)$type['max_advance_days'];

        $hostId = (int)($type['default_host_id'] ?? 0);
        if (!$hostId) {
            // si no hay host, tomar el primer usuario activo del tenant
            $hostId = (int)$this->db->val('SELECT id FROM users WHERE tenant_id=? AND is_active=1 ORDER BY id LIMIT 1', [$tenant->id]);
        }
        if (!$hostId) return [];

        // Validar ventana global
        $today = date('Y-m-d');
        $earliest = date('Y-m-d', strtotime('+' . $minHours . ' hours'));
        $latest   = date('Y-m-d', strtotime('+' . $maxDays . ' days'));
        if ($date < $earliest || $date > $latest) return [];

        $weekday = (int)date('w', strtotime($date)); // 0=Dom .. 6=Sab

        $availability = $this->db->all(
            'SELECT start_time, end_time FROM meeting_availability
             WHERE tenant_id=? AND user_id=? AND weekday=? AND is_active=1
             ORDER BY start_time',
            [$tenant->id, $hostId, $weekday]
        );
        if (!$availability) return [];

        // bloqueos full-day o parciales que tocan esta fecha
        $blocks = $this->db->all(
            'SELECT date_start, date_end, start_time, end_time, is_full_day
             FROM meeting_blocked_dates
             WHERE tenant_id=? AND (user_id=? OR user_id IS NULL)
               AND date_start <= ? AND date_end >= ?',
            [$tenant->id, $hostId, $date, $date]
        );
        foreach ($blocks as $b) {
            if ((int)$b['is_full_day'] === 1) return [];
        }

        // reuniones existentes del host en esa fecha (no canceladas)
        $existing = $this->db->all(
            "SELECT scheduled_at, ends_at FROM meetings
             WHERE tenant_id=? AND host_user_id=? AND DATE(scheduled_at)=?
               AND status IN ('scheduled','confirmed','completed')",
            [$tenant->id, $hostId, $date]
        );

        // construir intervalos ocupados (en epoch)
        $busy = [];
        foreach ($existing as $m) {
            $start = strtotime($m['scheduled_at']) - ($bufferBefore * 60);
            $end   = strtotime($m['ends_at']) + ($bufferAfter * 60);
            $busy[] = [$start, $end];
        }
        foreach ($blocks as $b) {
            if ((int)$b['is_full_day'] === 0 && !empty($b['start_time']) && !empty($b['end_time'])) {
                $start = strtotime($date . ' ' . $b['start_time']);
                $end   = strtotime($date . ' ' . $b['end_time']);
                $busy[] = [$start, $end];
            }
        }

        $now = time();
        $minBookable = $now + ($minHours * 3600);

        $slots = [];
        foreach ($availability as $win) {
            $winStart = strtotime($date . ' ' . $win['start_time']);
            $winEnd   = strtotime($date . ' ' . $win['end_time']);
            // generar candidatos cada $step
            for ($t = $winStart; $t + ($duration * 60) <= $winEnd; $t += $step * 60) {
                $slotStart = $t;
                $slotEnd   = $t + ($duration * 60);
                if ($slotStart < $minBookable) continue;

                $clash = false;
                foreach ($busy as [$bs, $be]) {
                    if ($slotStart < $be && $slotEnd > $bs) { $clash = true; break; }
                }
                if ($clash) continue;

                $slots[] = [
                    'start' => date('H:i', $slotStart),
                    'end'   => date('H:i', $slotEnd),
                    'iso'   => date('Y-m-d\TH:i:s', $slotStart),
                    'label' => date('H:i', $slotStart),
                ];
            }
        }
        // dedup (varias franjas pueden generar mismo slot)
        $seen = []; $unique = [];
        foreach ($slots as $s) {
            if (isset($seen[$s['start']])) continue;
            $seen[$s['start']] = 1;
            $unique[] = $s;
        }
        return $unique;
    }

    /* ─────────────────────────────────────────────────────────
       Confirmar reserva (POST)
       ───────────────────────────────────────────────────────── */
    public function store(array $params): void
    {
        [$tenant, $settings] = $this->resolveContext((string)$params['slug']);
        $this->guardEnabled($tenant, $settings);

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo 'Sesión expirada. Volvé a la página anterior y recargá.';
            return;
        }

        $type = $this->db->one(
            'SELECT * FROM meeting_types WHERE tenant_id=? AND slug=? AND is_active=1',
            [$tenant->id, (string)$params['type']]
        );
        if (!$type) $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug));

        $date = (string)$this->input('date', '');
        $time = (string)$this->input('time', '');
        $name = trim((string)$this->input('name', ''));
        $email = trim((string)$this->input('email', ''));
        $phone = trim((string)$this->input('phone', ''));
        $companyName = trim((string)$this->input('company', ''));
        $notes = trim((string)$this->input('notes', ''));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
            $this->session->flash('error', 'Fecha u hora inválida.');
            $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/' . $type['slug']);
        }
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Nombre y email válidos son obligatorios.');
            $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/' . $type['slug']);
        }
        if ((int)$settings['require_phone'] === 1 && $phone === '') {
            $this->session->flash('error', 'El teléfono es obligatorio.');
            $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/' . $type['slug']);
        }
        if ((int)$settings['require_company'] === 1 && $companyName === '') {
            $this->session->flash('error', 'La empresa es obligatoria.');
            $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/' . $type['slug']);
        }

        // Re-validar disponibilidad del slot (anti-race-condition)
        $available = $this->computeSlotsForDate($tenant, $type, $date);
        $found = false;
        foreach ($available as $s) {
            if ($s['start'] === $time) { $found = true; break; }
        }
        if (!$found) {
            $this->session->flash('error', 'Ese horario ya no está disponible. Por favor elegí otro.');
            $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/' . $type['slug']);
        }

        $duration = (int)$type['duration_minutes'];
        $scheduledAt = $date . ' ' . $time . ':00';
        $endsAt = date('Y-m-d H:i:s', strtotime($scheduledAt) + ($duration * 60));

        // recolectar respuestas a custom questions
        $questions = $this->parseQuestions($type['custom_questions']);
        $answers = [];
        foreach ($questions as $i => $q) {
            $val = $this->input('q_' . $i);
            if ($q['required'] && (is_null($val) || trim((string)$val) === '')) {
                $this->session->flash('error', 'Falta responder: ' . $q['label']);
                $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/' . $type['slug']);
            }
            if (!is_null($val) && trim((string)$val) !== '') {
                $answers[] = ['label' => $q['label'], 'value' => is_array($val) ? implode(', ', $val) : (string)$val];
            }
        }

        // company match por dominio
        $companyId = Helpers::findCompanyByEmail($tenant->id, $email);
        $contactId = Helpers::upsertContact($tenant->id, $companyId, $name, $email, $phone ?: null);

        $code = $this->generateCode($tenant->id);
        $token = bin2hex(random_bytes(16));

        $hostId = (int)($type['default_host_id'] ?? 0) ?: null;

        $statusInitial = (int)$type['requires_confirmation'] === 1 ? 'scheduled' : 'confirmed';

        $id = $this->db->insert('meetings', [
            'tenant_id'        => $tenant->id,
            'code'             => $code,
            'meeting_type_id'  => (int)$type['id'],
            'host_user_id'     => $hostId,
            'company_id'       => $companyId,
            'contact_id'       => $contactId,
            'customer_name'    => $name,
            'customer_email'   => $email,
            'customer_phone'   => $phone ?: null,
            'customer_company' => $companyName ?: null,
            'subject'          => $type['name'],
            'notes'            => $notes ?: null,
            'status'           => $statusInitial,
            'scheduled_at'     => $scheduledAt,
            'ends_at'          => $endsAt,
            'duration_minutes' => $duration,
            'timezone'         => $settings['timezone'] ?? 'America/Santo_Domingo',
            'location_type'    => $type['location_type'],
            'location_value'   => $type['location_value'],
            'meeting_url'      => null,
            'custom_answers'   => $answers ? json_encode($answers, JSON_UNESCAPED_UNICODE) : null,
            'public_token'     => $token,
            'source'           => 'public',
            'ip_address'       => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
            'user_agent'       => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        $meeting = $this->db->one('SELECT * FROM meetings WHERE id=?', [$id]);

        // emails (cliente + internos)
        $mc = new MeetingController();
        $mc->sendBookingEmail($tenant, $meeting, $statusInitial === 'confirmed' ? 'created' : 'created');

        // Análisis IA automático (best-effort, no bloquea si falla)
        if ((int)($settings['ai_auto_analyze'] ?? 1) === 1) {
            \App\Controllers\MeetingAiController::analyzeAfterBooking($tenant, $id);
        }

        $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/confirmation/' . $token);
    }

    /* ─────────────────────────────────────────────────────────
       Página de confirmación
       ───────────────────────────────────────────────────────── */
    public function confirmation(array $params): void
    {
        [$tenant, $settings] = $this->resolveContext((string)$params['slug']);

        $meeting = $this->db->one(
            "SELECT m.*, mt.name AS type_name, mt.color AS type_color, mt.icon AS type_icon, mt.allow_cancel, mt.allow_reschedule, mt.location_type, mt.location_value
             FROM meetings m
             LEFT JOIN meeting_types mt ON mt.id = m.meeting_type_id
             WHERE m.tenant_id=? AND m.public_token=?",
            [$tenant->id, (string)$params['token']]
        );
        if (!$meeting) {
            $this->renderPublic('booking/not_found', ['title' => 'No encontrada', 'tenant' => $tenant, 'settings' => $settings]);
            return;
        }

        $this->renderPublic('booking/confirmation', [
            'title'    => '¡Reserva confirmada!',
            'tenant'   => $tenant,
            'settings' => $settings,
            'meeting'  => $meeting,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       Gestionar (cancelar / reprogramar) desde el enlace público
       ───────────────────────────────────────────────────────── */
    public function manage(array $params): void
    {
        [$tenant, $settings] = $this->resolveContext((string)$params['slug']);

        $meeting = $this->db->one(
            "SELECT m.*, mt.name AS type_name, mt.slug AS type_slug, mt.color AS type_color, mt.icon AS type_icon, mt.allow_cancel, mt.allow_reschedule
             FROM meetings m
             LEFT JOIN meeting_types mt ON mt.id = m.meeting_type_id
             WHERE m.tenant_id=? AND m.public_token=?",
            [$tenant->id, (string)$params['token']]
        );
        if (!$meeting) {
            $this->renderPublic('booking/not_found', ['title' => 'No encontrada', 'tenant' => $tenant, 'settings' => $settings]);
            return;
        }

        $this->renderPublic('booking/manage', [
            'title'    => 'Gestionar reserva',
            'tenant'   => $tenant,
            'settings' => $settings,
            'meeting'  => $meeting,
        ]);
    }

    public function cancelByToken(array $params): void
    {
        [$tenant, $settings] = $this->resolveContext((string)$params['slug']);
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(419); echo 'CSRF'; return;
        }

        $meeting = $this->db->one('SELECT * FROM meetings WHERE tenant_id=? AND public_token=?', [$tenant->id, (string)$params['token']]);
        if (!$meeting) $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug));

        if (in_array($meeting['status'], ['cancelled','completed','no_show'], true)) {
            $this->session->flash('error', 'Esta reunión ya no se puede cancelar.');
            $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/manage/' . $meeting['public_token']);
        }

        $reason = trim((string)$this->input('cancel_reason',''));
        $this->db->update('meetings', [
            'status'        => 'cancelled',
            'cancel_reason' => $reason ?: null,
            'cancelled_at'  => date('Y-m-d H:i:s'),
            'cancelled_by'  => 'customer',
        ], 'id=?', [(int)$meeting['id']]);

        $meeting = $this->db->one('SELECT * FROM meetings WHERE id=?', [(int)$meeting['id']]);
        $mc = new MeetingController();
        $mc->sendBookingEmail($tenant, $meeting, 'cancelled', $reason);

        $this->session->flash('success', 'Reserva cancelada.');
        $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/manage/' . $meeting['public_token']);
    }

    public function rescheduleByToken(array $params): void
    {
        [$tenant, $settings] = $this->resolveContext((string)$params['slug']);
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(419); echo 'CSRF'; return;
        }

        $meeting = $this->db->one('SELECT * FROM meetings WHERE tenant_id=? AND public_token=?', [$tenant->id, (string)$params['token']]);
        if (!$meeting) $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug));

        $type = $this->db->one('SELECT * FROM meeting_types WHERE id=?', [(int)$meeting['meeting_type_id']]);
        if (!$type || (int)$type['allow_reschedule'] !== 1) {
            $this->session->flash('error', 'Este tipo de reunión no permite reprogramar.');
            $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/manage/' . $meeting['public_token']);
        }

        $date = (string)$this->input('date', '');
        $time = (string)$this->input('time', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
            $this->session->flash('error', 'Fecha u hora inválida.');
            $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/manage/' . $meeting['public_token']);
        }

        $available = $this->computeSlotsForDate($tenant, $type, $date);
        $found = false;
        foreach ($available as $s) { if ($s['start'] === $time) { $found = true; break; } }
        if (!$found) {
            $this->session->flash('error', 'Ese horario ya no está disponible.');
            $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/manage/' . $meeting['public_token']);
        }

        $duration = (int)$type['duration_minutes'];
        $scheduledAt = $date . ' ' . $time . ':00';
        $endsAt = date('Y-m-d H:i:s', strtotime($scheduledAt) + ($duration * 60));

        $this->db->update('meetings', [
            'scheduled_at'    => $scheduledAt,
            'ends_at'         => $endsAt,
            'status'          => (int)$type['requires_confirmation'] === 1 ? 'scheduled' : 'confirmed',
        ], 'id=?', [(int)$meeting['id']]);

        $meeting = $this->db->one('SELECT * FROM meetings WHERE id=?', [(int)$meeting['id']]);
        $mc = new MeetingController();
        $mc->sendBookingEmail($tenant, $meeting, 'rescheduled');

        $this->session->flash('success', 'Reserva reprogramada.');
        $this->redirect('/book/' . rawurlencode($settings['public_slug'] ?: $tenant->slug) . '/manage/' . $meeting['public_token']);
    }

    /* ─────────────────────────────────────────────────────────
       Helpers
       ───────────────────────────────────────────────────────── */

    /**
     * Resuelve un slug público a [Tenant, settings].
     * Acepta tanto el public_slug de meeting_settings como el tenant->slug.
     */
    protected function resolveContext(string $slug): array
    {
        // 1) Probar primero por public_slug en meeting_settings
        $row = $this->db->one('SELECT * FROM meeting_settings WHERE public_slug=?', [$slug]);
        $tenant = null;
        if ($row) {
            $tenant = Tenant::find((int)$row['tenant_id']);
        }
        // 2) Si no se encontró, probar como tenant slug
        if (!$tenant) {
            $tenant = Tenant::resolve($slug);
            if ($tenant) {
                $row = $this->db->one('SELECT * FROM meeting_settings WHERE tenant_id=?', [$tenant->id]);
            }
        }
        if (!$tenant) {
            http_response_code(404);
            $this->renderPublic('booking/not_found', ['title' => 'No encontrado']);
            exit;
        }
        $this->app->tenant = $tenant;

        $defaults = (new MeetingController())->getSettings($tenant->id);
        $settings = $row ? array_merge($defaults, $row) : $defaults;
        if (empty($settings['public_slug'])) $settings['public_slug'] = $tenant->slug;

        return [$tenant, $settings];
    }

    /** Bloquea el render si el plan no tiene meetings o el tenant lo desactivó. */
    protected function guardEnabled(Tenant $tenant, array $settings): void
    {
        if (!Plan::has($tenant, 'meetings') || (int)($settings['is_enabled'] ?? 1) === 0) {
            http_response_code(404);
            $this->renderPublic('booking/not_found', ['title' => 'No disponible', 'tenant' => $tenant, 'settings' => $settings]);
            exit;
        }
    }

    protected function guardEnabledJson(Tenant $tenant, array $settings): void
    {
        if (!Plan::has($tenant, 'meetings') || (int)($settings['is_enabled'] ?? 1) === 0) {
            $this->json(['ok' => false, 'error' => 'disabled'], 404);
        }
    }

    protected function parseQuestions(?string $json): array
    {
        if (!$json) return [];
        $arr = json_decode($json, true);
        if (!is_array($arr)) return [];
        $out = [];
        foreach ($arr as $q) {
            if (!is_array($q) || empty($q['label'])) continue;
            $out[] = [
                'label'    => (string)$q['label'],
                'type'     => $q['type'] ?? 'text',
                'required' => !empty($q['required']),
                'options'  => $q['options'] ?? '',
            ];
        }
        return $out;
    }

    protected function generateCode(int $tenantId): string
    {
        return (new MeetingController())->generateCode($tenantId);
    }

    protected function renderPublic(string $tpl, array $data = []): void
    {
        echo $this->view->render($tpl, $data, 'public');
    }
}
