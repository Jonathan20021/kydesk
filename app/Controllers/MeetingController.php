<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Mailer;

/**
 * Módulo de Agenda de Reuniones (Calendly-style).
 * Disponible en planes Business / Enterprise.
 *
 * Cubre:
 *   · Dashboard con próximas reuniones, KPIs y calendario
 *   · CRUD de Meeting Types (tipos de cita configurables)
 *   · Disponibilidad semanal por host
 *   · Días bloqueados (vacaciones, feriados)
 *   · Detalle / cancelación / reprogramación / confirmación de reuniones
 *   · Configuración de la página pública
 *   · Endpoint JSON de slots para el calendario
 */
class MeetingController extends Controller
{
    public const WEEKDAYS = [
        0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
        4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
    ];

    public const STATUS_LABELS = [
        'scheduled'    => ['Agendada',    'badge-blue',    'calendar'],
        'confirmed'    => ['Confirmada',  'badge-emerald', 'check-circle'],
        'cancelled'    => ['Cancelada',   'badge-rose',    'x-circle'],
        'completed'    => ['Completada',  'badge-purple',  'check-check'],
        'no_show'      => ['No-show',     'badge-amber',   'user-x'],
        'rescheduled'  => ['Reprogramada','badge-gray',    'rotate-cw'],
    ];

    /* ─────────────────────────────────────────────────────────
       DASHBOARD
       ───────────────────────────────────────────────────────── */
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.view');

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $in30 = date('Y-m-d H:i:s', strtotime('+30 days'));
        $last30 = date('Y-m-d H:i:s', strtotime('-30 days'));

        $stats = [
            'today'     => (int)$this->db->val("SELECT COUNT(*) FROM meetings WHERE tenant_id=? AND DATE(scheduled_at)=? AND status IN ('scheduled','confirmed')", [$tenant->id, $today]),
            'upcoming'  => (int)$this->db->val("SELECT COUNT(*) FROM meetings WHERE tenant_id=? AND scheduled_at >= ? AND status IN ('scheduled','confirmed')", [$tenant->id, $now]),
            'this_month'=> (int)$this->db->val("SELECT COUNT(*) FROM meetings WHERE tenant_id=? AND YEAR(scheduled_at)=YEAR(CURDATE()) AND MONTH(scheduled_at)=MONTH(CURDATE())", [$tenant->id]),
            'completed' => (int)$this->db->val("SELECT COUNT(*) FROM meetings WHERE tenant_id=? AND status='completed' AND scheduled_at >= ?", [$tenant->id, $last30]),
            'cancelled' => (int)$this->db->val("SELECT COUNT(*) FROM meetings WHERE tenant_id=? AND status='cancelled' AND scheduled_at >= ?", [$tenant->id, $last30]),
            'no_show'   => (int)$this->db->val("SELECT COUNT(*) FROM meetings WHERE tenant_id=? AND status='no_show' AND scheduled_at >= ?", [$tenant->id, $last30]),
            'total'     => (int)$this->db->val("SELECT COUNT(*) FROM meetings WHERE tenant_id=?", [$tenant->id]),
        ];

        $upcoming = $this->db->all(
            "SELECT m.*, mt.name AS type_name, mt.color AS type_color, mt.icon AS type_icon,
                    u.name AS host_name, u.email AS host_email,
                    c.name AS company_name
             FROM meetings m
             LEFT JOIN meeting_types mt ON mt.id = m.meeting_type_id
             LEFT JOIN users u ON u.id = m.host_user_id
             LEFT JOIN companies c ON c.id = m.company_id
             WHERE m.tenant_id = ? AND m.scheduled_at >= ? AND m.status IN ('scheduled','confirmed')
             ORDER BY m.scheduled_at ASC LIMIT 8",
            [$tenant->id, $now]
        );

        $recent = $this->db->all(
            "SELECT m.*, mt.name AS type_name, mt.color AS type_color, mt.icon AS type_icon,
                    u.name AS host_name
             FROM meetings m
             LEFT JOIN meeting_types mt ON mt.id = m.meeting_type_id
             LEFT JOIN users u ON u.id = m.host_user_id
             WHERE m.tenant_id = ? AND m.scheduled_at < ?
             ORDER BY m.scheduled_at DESC LIMIT 8",
            [$tenant->id, $now]
        );

        $byTypeRows = $this->db->all(
            "SELECT mt.id, mt.slug, mt.name, mt.color, mt.icon, mt.duration_minutes,
                    (SELECT COUNT(*) FROM meetings m WHERE m.meeting_type_id = mt.id AND m.scheduled_at >= ?) AS upcoming_count
             FROM meeting_types mt
             WHERE mt.tenant_id = ? AND mt.is_active = 1
             ORDER BY mt.sort_order, mt.name",
            [$now, $tenant->id]
        );

        $settings = $this->getSettings($tenant->id);

        $this->render('meetings/index', [
            'title'    => 'Agenda de reuniones',
            'stats'    => $stats,
            'upcoming' => $upcoming,
            'recent'   => $recent,
            'types'    => $byTypeRows,
            'settings' => $settings,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       LISTADO DE REUNIONES
       ───────────────────────────────────────────────────────── */
    public function listAll(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.view');

        $status   = (string)$this->input('status', '');
        $typeId   = (int)$this->input('type_id', 0);
        $hostId   = (int)$this->input('host_id', 0);
        $q        = trim((string)$this->input('q', ''));
        $from     = (string)$this->input('from', '');
        $to       = (string)$this->input('to', '');

        $where = ['m.tenant_id = ?']; $args = [$tenant->id];
        if ($status && isset(self::STATUS_LABELS[$status])) { $where[] = 'm.status = ?'; $args[] = $status; }
        if ($typeId) { $where[] = 'm.meeting_type_id = ?'; $args[] = $typeId; }
        if ($hostId) { $where[] = 'm.host_user_id = ?'; $args[] = $hostId; }
        if ($q !== '') {
            $where[] = '(m.customer_name LIKE ? OR m.customer_email LIKE ? OR m.customer_company LIKE ? OR m.code LIKE ? OR m.subject LIKE ?)';
            $like = "%$q%";
            $args[] = $like; $args[] = $like; $args[] = $like; $args[] = $like; $args[] = $like;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'm.scheduled_at >= ?'; $args[] = $from . ' 00:00:00'; }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $where[] = 'm.scheduled_at <= ?'; $args[] = $to . ' 23:59:59'; }

        $meetings = $this->db->all(
            "SELECT m.id, m.code, m.customer_name, m.customer_email, m.scheduled_at, m.duration_minutes, m.status,
                    m.ai_intent, m.ai_sentiment, m.ai_urgency,
                    mt.name AS type_name, mt.color AS type_color, mt.icon AS type_icon,
                    u.name AS host_name, c.name AS company_name
             FROM meetings m
             LEFT JOIN meeting_types mt ON mt.id = m.meeting_type_id
             LEFT JOIN users u ON u.id = m.host_user_id
             LEFT JOIN companies c ON c.id = m.company_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY m.scheduled_at DESC LIMIT 200",
            $args
        );

        $types  = $this->db->all('SELECT id, name, color, icon FROM meeting_types WHERE tenant_id=? ORDER BY sort_order, name', [$tenant->id]);
        $hosts  = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? AND is_active=1 ORDER BY name', [$tenant->id]);

        $this->render('meetings/list', [
            'title'    => 'Reuniones',
            'meetings' => $meetings,
            'types'    => $types,
            'hosts'    => $hosts,
            'filters'  => compact('status','typeId','hostId','q','from','to'),
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       CALENDARIO
       ───────────────────────────────────────────────────────── */
    public function calendar(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.view');

        $month = (string)$this->input('month', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
        $start = $month . '-01';
        $end   = date('Y-m-t', strtotime($start));

        $meetings = $this->db->all(
            "SELECT m.id, m.code, m.customer_name, m.scheduled_at, m.ends_at, m.status, m.duration_minutes,
                    mt.name AS type_name, mt.color AS type_color, mt.icon AS type_icon,
                    u.name AS host_name
             FROM meetings m
             LEFT JOIN meeting_types mt ON mt.id = m.meeting_type_id
             LEFT JOIN users u ON u.id = m.host_user_id
             WHERE m.tenant_id = ? AND DATE(m.scheduled_at) BETWEEN ? AND ?
             ORDER BY m.scheduled_at ASC",
            [$tenant->id, $start, $end]
        );

        $this->render('meetings/calendar', [
            'title'    => 'Calendario',
            'month'    => $month,
            'start'    => $start,
            'end'      => $end,
            'meetings' => $meetings,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       MEETING TYPES (CRUD)
       ───────────────────────────────────────────────────────── */
    public function typesIndex(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');

        $types = $this->db->all(
            "SELECT mt.*, u.name AS host_name,
                    (SELECT COUNT(*) FROM meetings m WHERE m.meeting_type_id = mt.id) AS total_bookings
             FROM meeting_types mt
             LEFT JOIN users u ON u.id = mt.default_host_id
             WHERE mt.tenant_id = ?
             ORDER BY mt.sort_order, mt.name",
            [$tenant->id]
        );

        $this->render('meetings/types_index', [
            'title' => 'Tipos de reunión',
            'types' => $types,
        ]);
    }

    public function typesCreate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');

        $hosts = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? AND is_active=1 ORDER BY name', [$tenant->id]);

        $this->render('meetings/types_form', [
            'title' => 'Nuevo tipo de reunión',
            'type'  => null,
            'hosts' => $hosts,
        ]);
    }

    public function typesEdit(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');

        $type = $this->db->one('SELECT * FROM meeting_types WHERE id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        if (!$type) $this->redirect('/t/' . $tenant->slug . '/meetings/types');

        $hosts = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? AND is_active=1 ORDER BY name', [$tenant->id]);

        $this->render('meetings/types_form', [
            'title' => 'Editar tipo · ' . $type['name'],
            'type'  => $type,
            'hosts' => $hosts,
        ]);
    }

    public function typesStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');
        $this->validateCsrf();

        $name = trim((string)$this->input('name', ''));
        if ($name === '') {
            $this->session->flash('error', 'El nombre del tipo de reunión es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/meetings/types/create');
        }

        $slug = Helpers::slug($name) ?: 'reunion-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $existing = $this->db->val('SELECT id FROM meeting_types WHERE tenant_id=? AND slug=?', [$tenant->id, $slug]);
        if ($existing) $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);

        $data = $this->buildTypeData($tenant->id, $name, $slug);

        $this->db->insert('meeting_types', $data);
        $this->session->flash('success', 'Tipo de reunión creado.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/types');
    }

    public function typesUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $existing = $this->db->one('SELECT * FROM meeting_types WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$existing) $this->redirect('/t/' . $tenant->slug . '/meetings/types');

        $name = trim((string)$this->input('name', $existing['name']));
        if ($name === '') {
            $this->session->flash('error', 'El nombre es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/meetings/types/' . $id);
        }

        $data = $this->buildTypeData($tenant->id, $name, $existing['slug']);
        unset($data['tenant_id'], $data['slug']);

        $this->db->update('meeting_types', $data, 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Tipo de reunión actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/types/' . $id);
    }

    public function typesToggle(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $current = (int)$this->db->val('SELECT is_active FROM meeting_types WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->update('meeting_types', ['is_active' => $current ? 0 : 1], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', $current ? 'Tipo desactivado.' : 'Tipo reactivado.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/types');
    }

    public function typesDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $count = (int)$this->db->val('SELECT COUNT(*) FROM meetings WHERE meeting_type_id=?', [$id]);
        if ($count > 0) {
            $this->session->flash('error', 'No podés eliminar un tipo con reuniones registradas. Desactivalo en su lugar.');
            $this->redirect('/t/' . $tenant->slug . '/meetings/types');
        }
        $this->db->delete('meeting_types', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Tipo de reunión eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/types');
    }

    protected function buildTypeData(int $tenantId, string $name, string $slug): array
    {
        $locType = (string)$this->input('location_type', 'virtual');
        if (!in_array($locType, ['virtual','phone','in_person','custom'], true)) $locType = 'virtual';

        $questions = (array)$this->input('questions', []);
        $cleanQuestions = [];
        foreach ($questions as $q) {
            if (!is_array($q)) continue;
            $label = trim((string)($q['label'] ?? ''));
            if ($label === '') continue;
            $cleanQuestions[] = [
                'label'    => $label,
                'type'     => in_array(($q['type'] ?? 'text'), ['text','textarea','select','phone','number'], true) ? $q['type'] : 'text',
                'required' => !empty($q['required']) ? 1 : 0,
                'options'  => trim((string)($q['options'] ?? '')),
            ];
        }

        $hostId = (int)$this->input('default_host_id', 0) ?: null;
        if ($hostId) {
            $valid = $this->db->val('SELECT id FROM users WHERE id=? AND tenant_id=?', [$hostId, $tenantId]);
            if (!$valid) $hostId = null;
        }

        return [
            'tenant_id'             => $tenantId,
            'slug'                  => $slug,
            'name'                  => $name,
            'description'           => trim((string)$this->input('description','')) ?: null,
            'duration_minutes'      => max(5, min(480, (int)$this->input('duration_minutes', 30))),
            'color'                 => substr((string)$this->input('color','#7c5cff'), 0, 20),
            'icon'                  => substr((string)$this->input('icon','video'), 0, 40) ?: 'video',
            'location_type'         => $locType,
            'location_value'        => trim((string)$this->input('location_value','')) ?: null,
            'buffer_before_minutes' => max(0, min(240, (int)$this->input('buffer_before_minutes', 0))),
            'buffer_after_minutes'  => max(0, min(240, (int)$this->input('buffer_after_minutes', 15))),
            'min_notice_hours'      => max(0, min(720, (int)$this->input('min_notice_hours', 4))),
            'max_advance_days'      => max(1, min(365, (int)$this->input('max_advance_days', 60))),
            'slot_step_minutes'     => max(5, min(120, (int)$this->input('slot_step_minutes', 30))),
            'default_host_id'       => $hostId,
            'requires_confirmation' => (int)$this->input('requires_confirmation', 0) ? 1 : 0,
            'allow_reschedule'      => (int)$this->input('allow_reschedule', 1) ? 1 : 0,
            'allow_cancel'          => (int)$this->input('allow_cancel', 1) ? 1 : 0,
            'send_reminders'        => (int)$this->input('send_reminders', 1) ? 1 : 0,
            'reminder_minutes'      => max(0, min(2880, (int)$this->input('reminder_minutes', 60))),
            'custom_questions'      => $cleanQuestions ? json_encode($cleanQuestions, JSON_UNESCAPED_UNICODE) : null,
            'redirect_url'          => trim((string)$this->input('redirect_url','')) ?: null,
            'is_active'             => (int)$this->input('is_active', 1) ? 1 : 0,
            'sort_order'            => (int)$this->input('sort_order', 0),
        ];
    }

    /* ─────────────────────────────────────────────────────────
       AVAILABILITY (DISPONIBILIDAD)
       ───────────────────────────────────────────────────────── */
    public function availability(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');

        $hostId = (int)$this->input('host_id', $this->auth->userId());
        $hosts = $this->db->all('SELECT id, name, email FROM users WHERE tenant_id=? AND is_active=1 ORDER BY name', [$tenant->id]);

        // si el host actual no es válido, tomar el primero
        if (!$hostId || !in_array($hostId, array_column($hosts, 'id'))) {
            $hostId = (int)($hosts[0]['id'] ?? 0);
        }

        $slots = [];
        if ($hostId) {
            $rows = $this->db->all(
                'SELECT * FROM meeting_availability WHERE tenant_id=? AND user_id=? ORDER BY weekday, start_time',
                [$tenant->id, $hostId]
            );
            foreach ($rows as $r) $slots[(int)$r['weekday']][] = $r;
        }

        $blocked = [];
        if ($hostId) {
            $blocked = $this->db->all(
                'SELECT * FROM meeting_blocked_dates WHERE tenant_id=? AND (user_id=? OR user_id IS NULL) AND date_end >= CURDATE() ORDER BY date_start',
                [$tenant->id, $hostId]
            );
        }

        $this->render('meetings/availability', [
            'title'   => 'Disponibilidad',
            'hostId'  => $hostId,
            'hosts'   => $hosts,
            'slots'   => $slots,
            'blocked' => $blocked,
        ]);
    }

    public function availabilityUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');
        $this->validateCsrf();

        $hostId = (int)$this->input('host_id', 0);
        $valid = $hostId ? $this->db->val('SELECT id FROM users WHERE id=? AND tenant_id=?', [$hostId, $tenant->id]) : null;
        if (!$valid) {
            $this->session->flash('error', 'Host inválido.');
            $this->redirect('/t/' . $tenant->slug . '/meetings/availability');
        }

        // Borrar slots actuales y reescribir
        $this->db->delete('meeting_availability', 'tenant_id=? AND user_id=?', [$tenant->id, $hostId]);

        $slots = (array)$this->input('slots', []);
        $count = 0;
        foreach ($slots as $weekday => $entries) {
            $weekday = (int)$weekday;
            if ($weekday < 0 || $weekday > 6) continue;
            if (!is_array($entries)) continue;
            foreach ($entries as $entry) {
                $start = (string)($entry['start'] ?? '');
                $end   = (string)($entry['end'] ?? '');
                $active = !empty($entry['active']);
                if (!$active) continue;
                if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) continue;
                if ($start >= $end) continue;
                $this->db->insert('meeting_availability', [
                    'tenant_id'  => $tenant->id,
                    'user_id'    => $hostId,
                    'weekday'    => $weekday,
                    'start_time' => $start . ':00',
                    'end_time'   => $end . ':00',
                    'is_active'  => 1,
                ]);
                $count++;
            }
        }

        $this->session->flash('success', "Disponibilidad guardada · $count franjas.");
        $this->redirect('/t/' . $tenant->slug . '/meetings/availability?host_id=' . $hostId);
    }

    public function blockedStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');
        $this->validateCsrf();

        $userId = (int)$this->input('user_id', 0) ?: null;
        if ($userId) {
            $valid = $this->db->val('SELECT id FROM users WHERE id=? AND tenant_id=?', [$userId, $tenant->id]);
            if (!$valid) $userId = null;
        }

        $start = (string)$this->input('date_start', '');
        $end   = (string)$this->input('date_end', $start);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            $this->session->flash('error', 'Fechas inválidas.');
            $this->redirect('/t/' . $tenant->slug . '/meetings/availability');
        }
        if ($end < $start) $end = $start;

        $isFullDay = (int)$this->input('is_full_day', 1) ? 1 : 0;
        $startTime = $isFullDay ? null : (preg_match('/^\d{2}:\d{2}$/', (string)$this->input('start_time','')) ? $this->input('start_time') . ':00' : null);
        $endTime   = $isFullDay ? null : (preg_match('/^\d{2}:\d{2}$/', (string)$this->input('end_time','')) ? $this->input('end_time') . ':00' : null);

        $this->db->insert('meeting_blocked_dates', [
            'tenant_id'   => $tenant->id,
            'user_id'     => $userId,
            'date_start'  => $start,
            'date_end'    => $end,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'reason'      => trim((string)$this->input('reason','')) ?: null,
            'is_full_day' => $isFullDay,
        ]);
        $this->session->flash('success', 'Bloqueo agregado.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/availability' . ($userId ? '?host_id=' . $userId : ''));
    }

    public function blockedDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');
        $this->validateCsrf();

        $this->db->delete('meeting_blocked_dates', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->session->flash('success', 'Bloqueo eliminado.');
        $this->back();
    }

    /* ─────────────────────────────────────────────────────────
       SETTINGS (página pública)
       ───────────────────────────────────────────────────────── */
    public function settings(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');

        $settings = $this->getSettings($tenant->id);
        $this->render('meetings/settings', [
            'title'    => 'Ajustes · Página pública',
            'settings' => $settings,
        ]);
    }

    public function settingsUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.config');
        $this->validateCsrf();

        $current = $this->getSettings($tenant->id);

        $publicSlug = Helpers::slug((string)$this->input('public_slug', $current['public_slug']));
        if ($publicSlug === '') $publicSlug = $tenant->slug;

        // Verificar unicidad del slug público (excluyendo este tenant)
        $clash = $this->db->val('SELECT tenant_id FROM meeting_settings WHERE public_slug=? AND tenant_id<>?', [$publicSlug, $tenant->id]);
        if ($clash) $publicSlug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);

        $data = [
            'is_enabled'         => (int)$this->input('is_enabled', 1) ? 1 : 0,
            'public_slug'        => $publicSlug,
            'page_title'         => trim((string)$this->input('page_title','')) ?: null,
            'page_description'   => trim((string)$this->input('page_description','')) ?: null,
            'logo_url'           => trim((string)$this->input('logo_url','')) ?: null,
            'primary_color'      => substr((string)$this->input('primary_color', '#7c5cff'), 0, 20),
            'welcome_message'    => trim((string)$this->input('welcome_message','')) ?: null,
            'success_message'    => trim((string)$this->input('success_message','')) ?: null,
            'timezone'           => substr((string)$this->input('timezone', $current['timezone']), 0, 80),
            'business_name'      => trim((string)$this->input('business_name','')) ?: null,
            'business_email'     => trim((string)$this->input('business_email','')) ?: null,
            'business_phone'     => trim((string)$this->input('business_phone','')) ?: null,
            'business_address'   => trim((string)$this->input('business_address','')) ?: null,
            'notify_new_booking' => (int)$this->input('notify_new_booking', 1) ? 1 : 0,
            'notify_emails'      => trim((string)$this->input('notify_emails','')) ?: null,
            'require_phone'      => (int)$this->input('require_phone', 0) ? 1 : 0,
            'require_company'    => (int)$this->input('require_company', 0) ? 1 : 0,
            'show_powered_by'    => (int)$this->input('show_powered_by', 1) ? 1 : 0,
            'ai_auto_analyze'    => (int)$this->input('ai_auto_analyze', 0) ? 1 : 0,
            'ai_public_suggester'=> (int)$this->input('ai_public_suggester', 0) ? 1 : 0,
            'ai_briefing_enabled'=> (int)$this->input('ai_briefing_enabled', 0) ? 1 : 0,
        ];

        if ($this->db->val('SELECT tenant_id FROM meeting_settings WHERE tenant_id=?', [$tenant->id])) {
            $this->db->update('meeting_settings', $data, 'tenant_id=?', [$tenant->id]);
        } else {
            $data['tenant_id'] = $tenant->id;
            $this->db->insert('meeting_settings', $data);
        }

        $this->session->flash('success', 'Ajustes guardados.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/settings');
    }

    /* ─────────────────────────────────────────────────────────
       MEETING DETAIL / ACTIONS
       ───────────────────────────────────────────────────────── */
    public function show(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.view');

        $meeting = $this->db->one(
            "SELECT m.*, mt.name AS type_name, mt.color AS type_color, mt.icon AS type_icon, mt.duration_minutes AS type_duration,
                    mt.allow_cancel, mt.allow_reschedule,
                    u.name AS host_name, u.email AS host_email,
                    c.name AS company_name
             FROM meetings m
             LEFT JOIN meeting_types mt ON mt.id = m.meeting_type_id
             LEFT JOIN users u ON u.id = m.host_user_id
             LEFT JOIN companies c ON c.id = m.company_id
             WHERE m.id = ? AND m.tenant_id = ?",
            [(int)$params['id'], $tenant->id]
        );
        if (!$meeting) $this->redirect('/t/' . $tenant->slug . '/meetings/list');

        $hosts = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? AND is_active=1 ORDER BY name', [$tenant->id]);
        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id=? ORDER BY name LIMIT 200', [$tenant->id]);

        // IA disponible? (para mostrar/ocultar cards)
        $aiAvailable = \App\Core\MeetingAi::guard($tenant)['ok'];
        $aiTopics = !empty($meeting['ai_topics']) ? (json_decode($meeting['ai_topics'], true) ?: []) : [];

        $this->render('meetings/show', [
            'title'       => 'Reunión ' . $meeting['code'],
            'meeting'     => $meeting,
            'hosts'       => $hosts,
            'companies'   => $companies,
            'aiAvailable' => $aiAvailable,
            'aiTopics'    => $aiTopics,
        ]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $existing = $this->db->one('SELECT * FROM meetings WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$existing) $this->redirect('/t/' . $tenant->slug . '/meetings/list');

        $hostId = (int)$this->input('host_user_id', 0) ?: null;
        if ($hostId) {
            $valid = $this->db->val('SELECT id FROM users WHERE id=? AND tenant_id=?', [$hostId, $tenant->id]);
            if (!$valid) $hostId = null;
        }
        $companyId = (int)$this->input('company_id', 0) ?: null;
        if ($companyId) {
            $valid = $this->db->val('SELECT id FROM companies WHERE id=? AND tenant_id=?', [$companyId, $tenant->id]);
            if (!$valid) $companyId = null;
        }

        $data = [
            'host_user_id'   => $hostId,
            'company_id'     => $companyId,
            'subject'        => trim((string)$this->input('subject','')) ?: null,
            'notes'          => trim((string)$this->input('notes','')) ?: null,
            'meeting_url'    => trim((string)$this->input('meeting_url','')) ?: null,
            'location_value' => trim((string)$this->input('location_value','')) ?: null,
            'customer_name'  => trim((string)$this->input('customer_name', $existing['customer_name'])),
            'customer_email' => trim((string)$this->input('customer_email', $existing['customer_email'])),
            'customer_phone' => trim((string)$this->input('customer_phone','')) ?: null,
            'customer_company'=> trim((string)$this->input('customer_company','')) ?: null,
        ];

        $this->db->update('meetings', $data, 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Reunión actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/' . $id);
    }

    public function cancel(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $reason = trim((string)$this->input('cancel_reason',''));

        $meeting = $this->db->one('SELECT * FROM meetings WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$meeting) $this->redirect('/t/' . $tenant->slug . '/meetings/list');

        $this->db->update('meetings', [
            'status'        => 'cancelled',
            'cancel_reason' => $reason ?: null,
            'cancelled_at'  => date('Y-m-d H:i:s'),
            'cancelled_by'  => 'host',
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        // notificar al cliente
        $this->sendBookingEmail($tenant, $meeting, 'cancelled', $reason);

        $this->session->flash('success', 'Reunión cancelada y notificación enviada.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/' . $id);
    }

    public function confirm(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $this->db->update('meetings', [
            'status' => 'confirmed',
            'confirmation_sent_at' => date('Y-m-d H:i:s'),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $meeting = $this->db->one('SELECT * FROM meetings WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if ($meeting) $this->sendBookingEmail($tenant, $meeting, 'confirmed');

        $this->session->flash('success', 'Reunión confirmada.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/' . $id);
    }

    public function complete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.edit');
        $this->validateCsrf();

        $this->db->update('meetings', ['status' => 'completed'], 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->session->flash('success', 'Marcada como completada.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/' . (int)$params['id']);
    }

    public function noShow(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.edit');
        $this->validateCsrf();

        $this->db->update('meetings', ['status' => 'no_show'], 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->session->flash('success', 'Marcada como no-show.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/' . (int)$params['id']);
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.delete');
        $this->validateCsrf();

        $this->db->delete('meetings', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->session->flash('success', 'Reunión eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/list');
    }

    /* ─────────────────────────────────────────────────────────
       MANUAL CREATE (desde el panel)
       ───────────────────────────────────────────────────────── */
    public function manualCreate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.create');

        $types = $this->db->all('SELECT id, name, duration_minutes, color FROM meeting_types WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name', [$tenant->id]);
        $hosts = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? AND is_active=1 ORDER BY name', [$tenant->id]);
        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id=? ORDER BY name LIMIT 200', [$tenant->id]);

        $this->render('meetings/manual', [
            'title'     => 'Agendar manualmente',
            'types'     => $types,
            'hosts'     => $hosts,
            'companies' => $companies,
        ]);
    }

    public function manualStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('meetings');
        $this->requireCan('meetings.create');
        $this->validateCsrf();

        $typeId = (int)$this->input('meeting_type_id', 0) ?: null;
        $type = $typeId ? $this->db->one('SELECT * FROM meeting_types WHERE id=? AND tenant_id=?', [$typeId, $tenant->id]) : null;
        $duration = $type ? (int)$type['duration_minutes'] : max(15, min(480, (int)$this->input('duration_minutes', 30)));

        $name = trim((string)$this->input('customer_name', ''));
        $email = trim((string)$this->input('customer_email', ''));
        $date = (string)$this->input('date', '');
        $time = (string)$this->input('time', '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
            $this->session->flash('error', 'Faltan datos: nombre, email válido, fecha y hora.');
            $this->redirect('/t/' . $tenant->slug . '/meetings/manual');
        }

        $scheduledAt = $date . ' ' . $time . ':00';
        $endsAt = date('Y-m-d H:i:s', strtotime($scheduledAt) + ($duration * 60));

        $hostId = (int)$this->input('host_user_id', 0) ?: ($type['default_host_id'] ?? null);
        if ($hostId) {
            $valid = $this->db->val('SELECT id FROM users WHERE id=? AND tenant_id=?', [$hostId, $tenant->id]);
            if (!$valid) $hostId = null;
        }
        $companyId = (int)$this->input('company_id', 0) ?: null;
        if ($companyId) {
            $valid = $this->db->val('SELECT id FROM companies WHERE id=? AND tenant_id=?', [$companyId, $tenant->id]);
            if (!$valid) $companyId = null;
        }

        $code = $this->generateCode($tenant->id);
        $token = bin2hex(random_bytes(16));

        $id = $this->db->insert('meetings', [
            'tenant_id'        => $tenant->id,
            'code'             => $code,
            'meeting_type_id'  => $typeId,
            'host_user_id'     => $hostId,
            'company_id'       => $companyId,
            'customer_name'    => $name,
            'customer_email'   => $email,
            'customer_phone'   => trim((string)$this->input('customer_phone','')) ?: null,
            'customer_company' => trim((string)$this->input('customer_company','')) ?: null,
            'subject'          => trim((string)$this->input('subject','')) ?: null,
            'notes'            => trim((string)$this->input('notes','')) ?: null,
            'status'           => 'confirmed',
            'scheduled_at'     => $scheduledAt,
            'ends_at'          => $endsAt,
            'duration_minutes' => $duration,
            'timezone'         => $this->getSettings($tenant->id)['timezone'] ?? 'America/Santo_Domingo',
            'location_type'    => $type['location_type'] ?? 'virtual',
            'location_value'   => $type['location_value'] ?? null,
            'meeting_url'      => trim((string)$this->input('meeting_url','')) ?: null,
            'public_token'     => $token,
            'source'           => 'manual',
        ]);

        // crear/actualizar contacto + asociar a empresa
        Helpers::upsertContact($tenant->id, $companyId, $name, $email, trim((string)$this->input('customer_phone','')) ?: null);

        $meeting = $this->db->one('SELECT * FROM meetings WHERE id=?', [$id]);
        if ($meeting) $this->sendBookingEmail($tenant, $meeting, 'confirmed');

        $this->session->flash('success', 'Reunión agendada manualmente. Se envió la confirmación al cliente.');
        $this->redirect('/t/' . $tenant->slug . '/meetings/' . $id);
    }

    /* ─────────────────────────────────────────────────────────
       Helpers de instancia
       ───────────────────────────────────────────────────────── */
    public function getSettings(int $tenantId): array
    {
        $row = $this->db->one('SELECT * FROM meeting_settings WHERE tenant_id=?', [$tenantId]);
        if (!$row) {
            return [
                'tenant_id'         => $tenantId,
                'is_enabled'        => 1,
                'public_slug'       => '',
                'page_title'        => null,
                'page_description'  => null,
                'logo_url'          => null,
                'primary_color'     => '#7c5cff',
                'welcome_message'   => null,
                'success_message'   => null,
                'timezone'          => 'America/Santo_Domingo',
                'business_name'     => null,
                'business_email'    => null,
                'business_phone'    => null,
                'business_address'  => null,
                'notify_new_booking'=> 1,
                'notify_emails'     => null,
                'require_phone'     => 0,
                'require_company'   => 0,
                'show_powered_by'   => 1,
                'custom_css'        => null,
                'ai_auto_analyze'   => 1,
                'ai_public_suggester' => 1,
                'ai_briefing_enabled' => 1,
            ];
        }
        return $row;
    }

    public function generateCode(int $tenantId): string
    {
        for ($i = 0; $i < 6; $i++) {
            $code = 'BK-' . str_pad((string)$tenantId, 2, '0', STR_PAD_LEFT) . '-' . Helpers::randomCode(6);
            $exists = $this->db->val('SELECT id FROM meetings WHERE tenant_id=? AND code=?', [$tenantId, $code]);
            if (!$exists) return $code;
        }
        return 'BK-' . $tenantId . '-' . time();
    }

    public function sendBookingEmail(\App\Core\Tenant $tenant, array $meeting, string $kind, ?string $reason = null): void
    {
        $settings = $this->getSettings($tenant->id);
        $type = null;
        if (!empty($meeting['meeting_type_id'])) {
            $type = $this->db->one('SELECT * FROM meeting_types WHERE id=?', [(int)$meeting['meeting_type_id']]);
        }
        $brandColor = $settings['primary_color'] ?: '#7c5cff';
        $publicSlug = $settings['public_slug'] ?: $tenant->slug;
        $base = rtrim((string)($this->app->config['app']['url'] ?? ''), '/');
        $manageUrl = $base . '/book/' . rawurlencode($publicSlug) . '/manage/' . $meeting['public_token'];
        $when = date('l, j \d\e F Y', strtotime($meeting['scheduled_at'])) . ' · ' . date('H:i', strtotime($meeting['scheduled_at']));

        $subjectMap = [
            'created'   => 'Reserva recibida · ' . ($settings['business_name'] ?: $tenant->name),
            'confirmed' => 'Reunión confirmada · ' . ($type['name'] ?? 'Tu cita'),
            'cancelled' => 'Reunión cancelada · ' . ($type['name'] ?? 'Tu cita'),
            'reminder'  => 'Recordatorio: tu reunión es en 1 hora',
            'rescheduled' => 'Reunión reprogramada · ' . ($type['name'] ?? 'Tu cita'),
        ];
        $subject = $subjectMap[$kind] ?? 'Actualización de tu reunión';

        $statusLine = '';
        switch ($kind) {
            case 'confirmed': $statusLine = '<p style="color:#10b981;font-weight:600">Tu reunión está confirmada.</p>'; break;
            case 'cancelled': $statusLine = '<p style="color:#ef4444;font-weight:600">La reunión fue cancelada.</p>' . ($reason ? '<p style="color:#6b6b78"><em>Motivo:</em> ' . htmlspecialchars($reason) . '</p>' : ''); break;
            case 'rescheduled': $statusLine = '<p style="color:#f59e0b;font-weight:600">Tu reunión fue reprogramada.</p>'; break;
            default: $statusLine = '<p style="color:#0ea5e9;font-weight:600">Recibimos tu reserva.</p>';
        }

        $body = $statusLine
            . '<table cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;width:100%;background:#fafafb;border:1px solid #ececef;border-radius:14px">'
            . '<tr><td style="padding:18px">'
            . '<div style="font-size:13px;color:#6b6b78;margin-bottom:6px">' . htmlspecialchars($type['name'] ?? 'Reunión') . '</div>'
            . '<div style="font-size:18px;font-weight:700;color:#16151b;margin-bottom:8px">' . htmlspecialchars($when) . '</div>'
            . '<div style="font-size:13px;color:#3a3946">Duración: ' . (int)$meeting['duration_minutes'] . ' min</div>'
            . '<div style="font-size:13px;color:#3a3946;margin-top:6px">Código: <strong>' . htmlspecialchars($meeting['code']) . '</strong></div>'
            . (!empty($meeting['meeting_url']) ? '<div style="font-size:13px;color:#3a3946;margin-top:6px">Enlace: <a href="' . htmlspecialchars($meeting['meeting_url']) . '" style="color:' . $brandColor . '">' . htmlspecialchars($meeting['meeting_url']) . '</a></div>' : '')
            . (!empty($meeting['location_value']) ? '<div style="font-size:13px;color:#3a3946;margin-top:6px">Lugar: ' . htmlspecialchars($meeting['location_value']) . '</div>' : '')
            . '</td></tr></table>'
            . '<p style="font-size:13px;color:#3a3946">Podés gestionar (cancelar / reprogramar) tu reserva desde el siguiente enlace:</p>';

        $html = Mailer::template($subject, $body, 'Gestionar mi reserva', $manageUrl);

        try {
            $mailer = new Mailer();
            $mailer->send($meeting['customer_email'], $subject, $html, null, [
                'from_name' => $settings['business_name'] ?: $tenant->name,
            ]);

            // notificar internos
            if (!empty($settings['notify_new_booking']) && in_array($kind, ['created','rescheduled'], true)) {
                $internalEmails = array_filter(array_map('trim', preg_split('/[,;\s]+/', (string)($settings['notify_emails'] ?? ''))));
                if (empty($internalEmails) && !empty($settings['business_email'])) {
                    $internalEmails = [$settings['business_email']];
                }
                if (!empty($meeting['host_user_id'])) {
                    $hostEmail = $this->db->val('SELECT email FROM users WHERE id=?', [(int)$meeting['host_user_id']]);
                    if ($hostEmail) $internalEmails[] = $hostEmail;
                }
                $internalEmails = array_unique(array_filter($internalEmails));
                if ($internalEmails) {
                    $internalSubject = '[Nueva reserva] ' . $meeting['customer_name'] . ' · ' . $when;
                    $internalBody = '<p><strong>' . htmlspecialchars($meeting['customer_name']) . '</strong> (' . htmlspecialchars($meeting['customer_email']) . ') reservó <strong>' . htmlspecialchars($type['name'] ?? 'una reunión') . '</strong> para <strong>' . htmlspecialchars($when) . '</strong>.</p>'
                        . (!empty($meeting['notes']) ? '<p>Mensaje: ' . nl2br(htmlspecialchars($meeting['notes'])) . '</p>' : '');
                    $internalHtml = Mailer::template('Nueva reserva', $internalBody, 'Ver en el panel', $base . '/t/' . $tenant->slug . '/meetings/' . $meeting['id']);
                    $mailer->send($internalEmails, $internalSubject, $internalHtml);
                }
            }
        } catch (\Throwable $e) { /* no bloquear UI por error de mail */ }
    }
}
