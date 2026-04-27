<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Events;
use App\Core\Mailer;

class TodoController extends Controller
{
    protected array $priorities = ['low', 'medium', 'high', 'urgent'];

    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.view');

        $userId = (int)$this->auth->userId();
        $viewMode = (string)$this->input('view', 'inbox');
        if (!in_array($viewMode, ['inbox', 'today', 'upcoming', 'overdue', 'important', 'delegated', 'completed', 'all'], true)) {
            $viewMode = 'inbox';
        }
        if (!empty($_GET['completed'])) $viewMode = 'completed';

        $listId = (int)$this->input('list', 0);
        $search = trim((string)$this->input('q', ''));
        $priority = (string)$this->input('priority', '');
        $assigneeFilter = (string)$this->input('assignee', '');

        $users = $this->db->all(
            'SELECT id, name, email, title FROM users WHERE tenant_id=? AND is_active=1 ORDER BY name',
            [$tenant->id]
        );
        $lists = $this->db->all(
            'SELECT * FROM todo_lists WHERE tenant_id=? AND user_id=? ORDER BY id',
            [$tenant->id, $userId]
        );

        [$whereSql, $args] = $this->buildTaskFilters($tenant->id, $userId, [
            'view' => $viewMode,
            'list_id' => $listId,
            'search' => $search,
            'priority' => $priority,
            'assignee' => $assigneeFilter,
        ]);

        $todos = $this->db->all(
            $this->taskSelectSql() . " WHERE $whereSql
             ORDER BY t.completed ASC,
                      CASE WHEN t.due_at IS NULL THEN 1 ELSE 0 END,
                      t.due_at ASC,
                      FIELD(t.priority,'urgent','high','medium','low'),
                      t.created_at DESC
             LIMIT 250",
            $args
        );

        $counts = $this->summaryCounts($tenant->id, $userId);
        $listCounts = $this->listCounts($tenant->id, $userId);
        $upcoming = $this->upcomingTasks($tenant->id, $userId);
        $teamLoad = $this->teamLoad($tenant->id);

        $this->render('todos/index', [
            'title' => 'Tareas',
            'lists' => $lists,
            'todos' => $todos,
            'users' => $users,
            'currentListId' => $listId,
            'viewMode' => $viewMode,
            'showCompleted' => $viewMode === 'completed',
            'search' => $search,
            'priorityFilter' => $priority,
            'assigneeFilter' => $assigneeFilter,
            'counts' => $counts,
            'listCounts' => $listCounts,
            'upcoming' => $upcoming,
            'teamLoad' => $teamLoad,
        ]);
    }

    public function storeList(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.create');
        $this->validateCsrf();

        $name = trim((string)$this->input('name'));
        if ($name === '') { $this->back(); return; }

        $this->db->insert('todo_lists', [
            'tenant_id' => $tenant->id,
            'user_id' => $this->auth->userId(),
            'name' => $name,
            'color' => $this->sanitizeColor((string)$this->input('color', '#6366f1')),
            'icon' => preg_replace('/[^a-z0-9-]/i', '', (string)$this->input('icon', 'list')) ?: 'list',
        ]);
        $this->session->flash('success', 'Lista creada.');
        $this->redirect('/t/' . $tenant->slug . '/todos');
    }

    public function deleteList(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.delete');
        $this->validateCsrf();
        $this->db->delete('todo_lists', 'id=? AND tenant_id=? AND user_id=?', [(int)$params['id'], $tenant->id, $this->auth->userId()]);
        $this->session->flash('success', 'Lista eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/todos');
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.create');
        $this->validateCsrf();

        $userId = (int)$this->auth->userId();
        $title = trim((string)$this->input('title'));
        if ($title === '') {
            $this->session->flash('error', 'Escribe el titulo de la tarea.');
            $this->back();
            return;
        }

        $assignedTo = $this->validUserId($tenant->id, (int)$this->input('assigned_to_id', $userId)) ?: $userId;
        $listId = $this->validListId($tenant->id, (int)$this->input('list_id', 0));
        $notify = (int)($this->input('email_notifications', 0) ? 1 : 0);

        $id = $this->db->insert('todos', [
            'tenant_id' => $tenant->id,
            'user_id' => $assignedTo,
            'created_by_id' => $userId,
            'assigned_to_id' => $assignedTo,
            'list_id' => $listId,
            'title' => $title,
            'description' => trim((string)$this->input('description', '')),
            'priority' => $this->cleanPriority((string)$this->input('priority', 'medium')),
            'due_at' => $this->cleanDateTime((string)$this->input('due_at', '')),
            'reminder_at' => $this->cleanDateTime((string)$this->input('reminder_at', '')),
            'labels' => $this->sanitizeLabels((string)$this->input('labels', '')),
            'estimate_minutes' => $this->cleanEstimate((string)$this->input('estimate_minutes', '')),
            'email_notifications' => $notify,
        ]);

        $task = $this->findTask($id, $tenant->id, $userId);
        $mail = $notify && $task ? $this->sendTaskEmail($task, 'created') : ['ok' => false, 'skipped' => true];
        Events::emit(Events::TODO_CREATED, $tenant->id, 'todo', $id, ['task' => $task], $userId);

        $this->session->flash('success', 'Tarea creada' . (($mail['ok'] ?? false) ? ' y notificada por email.' : '.'));
        $this->back();
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.edit');
        $this->validateCsrf();

        $userId = (int)$this->auth->userId();
        $id = (int)$params['id'];
        $task = $this->findTask($id, $tenant->id, $userId);
        if (!$task) { $this->back(); return; }

        $title = trim((string)$this->input('title'));
        if ($title === '') {
            $this->session->flash('error', 'El titulo no puede estar vacio.');
            $this->back();
            return;
        }

        $assignedTo = $this->validUserId($tenant->id, (int)$this->input('assigned_to_id', $task['assigned_to_id'] ?: $task['user_id'])) ?: (int)$task['user_id'];
        $reminderAt = $this->cleanDateTime((string)$this->input('reminder_at', ''));
        $dueAt = $this->cleanDateTime((string)$this->input('due_at', ''));
        $notify = (int)($this->input('email_notifications', 0) ? 1 : 0);

        $data = [
            'user_id' => $assignedTo,
            'assigned_to_id' => $assignedTo,
            'list_id' => $this->validListId($tenant->id, (int)$this->input('list_id', 0)),
            'title' => $title,
            'description' => trim((string)$this->input('description', '')),
            'priority' => $this->cleanPriority((string)$this->input('priority', 'medium')),
            'due_at' => $dueAt,
            'reminder_at' => $reminderAt,
            'labels' => $this->sanitizeLabels((string)$this->input('labels', '')),
            'estimate_minutes' => $this->cleanEstimate((string)$this->input('estimate_minutes', '')),
            'email_notifications' => $notify,
        ];

        if (($task['reminder_at'] ?? null) !== $reminderAt || ($task['due_at'] ?? null) !== $dueAt) {
            $data['reminder_sent_at'] = null;
        }

        $this->db->update('todos', $data, 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $updated = $this->findTask($id, $tenant->id, $userId);
        $mail = $notify && $updated ? $this->sendTaskEmail($updated, 'updated') : ['ok' => false, 'skipped' => true];
        Events::emit(Events::TODO_UPDATED, $tenant->id, 'todo', $id, ['task' => $updated], $userId);

        $this->session->flash('success', 'Tarea actualizada' . (($mail['ok'] ?? false) ? ' y notificada.' : '.'));
        $this->back();
    }

    public function toggle(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.edit');
        $this->validateCsrf();

        $userId = (int)$this->auth->userId();
        $id = (int)$params['id'];
        $task = $this->findTask($id, $tenant->id, $userId);
        if (!$task) { $this->back(); return; }

        $complete = (int)$task['completed'] ? 0 : 1;
        $this->db->update('todos', [
            'completed' => $complete,
            'completed_at' => $complete ? date('Y-m-d H:i:s') : null,
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $updated = $this->findTask($id, $tenant->id, $userId);
        Events::emit($complete ? Events::TODO_COMPLETED : Events::TODO_UPDATED, $tenant->id, 'todo', $id, ['task' => $updated], $userId);
        $this->back();
    }

    public function sendReminder(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.edit');
        $this->validateCsrf();

        $userId = (int)$this->auth->userId();
        $task = $this->findTask((int)$params['id'], $tenant->id, $userId);
        if (!$task) { $this->back(); return; }

        $result = $this->sendTaskEmail($task, 'reminder');
        if ($result['ok'] ?? false) {
            $this->db->update('todos', ['reminder_sent_at' => date('Y-m-d H:i:s')], 'id=? AND tenant_id=?', [(int)$task['id'], $tenant->id]);
            Events::emit(Events::TODO_REMINDER_SENT, $tenant->id, 'todo', (int)$task['id'], ['task' => $task, 'message_id' => $result['id'] ?? null], $userId);
            $this->session->flash('success', 'Recordatorio enviado por email.');
        } else {
            $this->session->flash('error', 'No se pudo enviar el recordatorio: ' . ($result['error'] ?? 'sin destinatario'));
        }
        $this->back();
    }

    public function sendDueReminders(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.edit');
        $this->validateCsrf();

        $userId = (int)$this->auth->userId();
        $tasks = $this->db->all(
            $this->taskSelectSql() . '
             WHERE t.tenant_id=?
               AND ' . $this->visibleClause('t') . '
               AND t.completed=0
               AND t.email_notifications=1
               AND (
                    (t.reminder_at IS NOT NULL AND t.reminder_at <= NOW() AND (t.reminder_sent_at IS NULL OR t.reminder_sent_at < t.reminder_at))
                    OR
                    (t.reminder_at IS NULL AND t.due_at IS NOT NULL AND t.due_at <= DATE_ADD(NOW(), INTERVAL 24 HOUR) AND (t.reminder_sent_at IS NULL OR t.reminder_sent_at < DATE_SUB(NOW(), INTERVAL 6 HOUR)))
               )
             ORDER BY COALESCE(t.reminder_at, t.due_at) ASC
             LIMIT 25',
            array_merge([$tenant->id], $this->visibleArgs($userId))
        );

        $sent = 0;
        $failed = 0;
        foreach ($tasks as $task) {
            $res = $this->sendTaskEmail($task, 'reminder');
            if ($res['ok'] ?? false) {
                $sent++;
                $this->db->update('todos', ['reminder_sent_at' => date('Y-m-d H:i:s')], 'id=? AND tenant_id=?', [(int)$task['id'], $tenant->id]);
                Events::emit(Events::TODO_REMINDER_SENT, $tenant->id, 'todo', (int)$task['id'], ['task' => $task, 'message_id' => $res['id'] ?? null], $userId);
            } else {
                $failed++;
            }
        }

        if ($sent === 0 && $failed === 0) {
            $this->session->flash('info', 'No hay alertas pendientes para enviar.');
        } elseif ($failed > 0) {
            $this->session->flash('error', "Alertas enviadas: $sent. Fallidas: $failed.");
        } else {
            $this->session->flash('success', "Alertas enviadas por email: $sent.");
        }
        $this->back();
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.delete');
        $this->validateCsrf();

        $userId = (int)$this->auth->userId();
        $id = (int)$params['id'];
        $task = $this->findTask($id, $tenant->id, $userId);
        if ($task) {
            $this->db->delete('todos', 'id=? AND tenant_id=?', [$id, $tenant->id]);
            Events::emit(Events::TODO_DELETED, $tenant->id, 'todo', $id, ['id' => $id, 'title' => $task['title']], $userId);
            $this->session->flash('success', 'Tarea eliminada.');
        }
        $this->back();
    }

    protected function buildTaskFilters(int $tenantId, int $userId, array $filters): array
    {
        $where = ['t.tenant_id=?', $this->visibleClause('t')];
        $args = array_merge([$tenantId], $this->visibleArgs($userId));

        $view = (string)($filters['view'] ?? 'inbox');
        if ($view === 'completed') {
            $where[] = 't.completed=1';
        } elseif ($view === 'today') {
            $where[] = 't.completed=0 AND DATE(t.due_at)=CURDATE()';
        } elseif ($view === 'upcoming') {
            $where[] = 't.completed=0 AND t.due_at IS NOT NULL AND DATE(t.due_at)>CURDATE()';
        } elseif ($view === 'overdue') {
            $where[] = 't.completed=0 AND t.due_at IS NOT NULL AND t.due_at<NOW()';
        } elseif ($view === 'important') {
            $where[] = "t.completed=0 AND t.priority IN ('urgent','high')";
        } elseif ($view === 'delegated') {
            $where[] = 't.completed=0 AND t.created_by_id=? AND t.assigned_to_id<>?';
            $args[] = $userId;
            $args[] = $userId;
        } elseif ($view !== 'all') {
            $where[] = 't.completed=0';
        }

        $listId = (int)($filters['list_id'] ?? 0);
        if ($listId > 0) {
            $where[] = 't.list_id=?';
            $args[] = $listId;
        }

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(t.title LIKE ? OR t.description LIKE ? OR t.labels LIKE ?)';
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
        }

        $priority = (string)($filters['priority'] ?? '');
        if (in_array($priority, $this->priorities, true)) {
            $where[] = 't.priority=?';
            $args[] = $priority;
        }

        $assignee = (string)($filters['assignee'] ?? '');
        if ($assignee === 'me') {
            $where[] = '(t.assigned_to_id=? OR (t.assigned_to_id IS NULL AND t.user_id=?))';
            $args[] = $userId;
            $args[] = $userId;
        } elseif (ctype_digit($assignee)) {
            $where[] = 't.assigned_to_id=?';
            $args[] = (int)$assignee;
        }

        return [implode(' AND ', array_map(fn($w) => '(' . $w . ')', $where)), $args];
    }

    protected function summaryCounts(int $tenantId, int $userId): array
    {
        $row = $this->db->one(
            "SELECT
                COALESCE(SUM(CASE WHEN t.completed=0 THEN 1 ELSE 0 END),0) AS pending,
                COALESCE(SUM(CASE WHEN t.completed=1 THEN 1 ELSE 0 END),0) AS done,
                COALESCE(SUM(CASE WHEN t.completed=0 AND DATE(t.due_at)=CURDATE() THEN 1 ELSE 0 END),0) AS today,
                COALESCE(SUM(CASE WHEN t.completed=0 AND t.due_at IS NOT NULL AND t.due_at<NOW() THEN 1 ELSE 0 END),0) AS overdue,
                COALESCE(SUM(CASE WHEN t.completed=0 AND t.priority IN ('urgent','high') THEN 1 ELSE 0 END),0) AS important,
                COALESCE(SUM(CASE WHEN t.completed=0 AND t.created_by_id=? AND t.assigned_to_id<>? THEN 1 ELSE 0 END),0) AS delegated,
                COALESCE(SUM(CASE WHEN t.completed=0 AND t.email_notifications=1 AND (
                    (t.reminder_at IS NOT NULL AND t.reminder_at<=NOW() AND (t.reminder_sent_at IS NULL OR t.reminder_sent_at<t.reminder_at))
                    OR
                    (t.reminder_at IS NULL AND t.due_at IS NOT NULL AND t.due_at<=DATE_ADD(NOW(), INTERVAL 24 HOUR) AND (t.reminder_sent_at IS NULL OR t.reminder_sent_at<DATE_SUB(NOW(), INTERVAL 6 HOUR)))
                ) THEN 1 ELSE 0 END),0) AS alerts
             FROM todos t
             WHERE t.tenant_id=? AND " . $this->visibleClause('t'),
            array_merge([$userId, $userId, $tenantId], $this->visibleArgs($userId))
        ) ?: [];

        $pending = (int)($row['pending'] ?? 0);
        $done = (int)($row['done'] ?? 0);
        $total = $pending + $done;
        return [
            'pending' => $pending,
            'done' => $done,
            'today' => (int)($row['today'] ?? 0),
            'overdue' => (int)($row['overdue'] ?? 0),
            'important' => (int)($row['important'] ?? 0),
            'delegated' => (int)($row['delegated'] ?? 0),
            'alerts' => (int)($row['alerts'] ?? 0),
            'progress' => $total > 0 ? (int)round(($done / $total) * 100) : 0,
        ];
    }

    protected function listCounts(int $tenantId, int $userId): array
    {
        $rows = $this->db->all(
            'SELECT t.list_id, COUNT(*) AS total
             FROM todos t
             WHERE t.tenant_id=? AND ' . $this->visibleClause('t') . ' AND t.completed=0
             GROUP BY t.list_id',
            array_merge([$tenantId], $this->visibleArgs($userId))
        );
        $out = [];
        foreach ($rows as $r) $out[(int)($r['list_id'] ?? 0)] = (int)$r['total'];
        return $out;
    }

    protected function upcomingTasks(int $tenantId, int $userId): array
    {
        return $this->db->all(
            $this->taskSelectSql() . '
             WHERE t.tenant_id=? AND ' . $this->visibleClause('t') . '
               AND t.completed=0 AND t.due_at IS NOT NULL
             ORDER BY t.due_at ASC
             LIMIT 6',
            array_merge([$tenantId], $this->visibleArgs($userId))
        );
    }

    protected function teamLoad(int $tenantId): array
    {
        return $this->db->all(
            'SELECT u.id, u.name, u.email, u.title, COUNT(t.id) AS open_tasks
             FROM users u
             LEFT JOIN todos t ON t.tenant_id=u.tenant_id AND t.assigned_to_id=u.id AND t.completed=0
             WHERE u.tenant_id=? AND u.is_active=1
             GROUP BY u.id, u.name, u.email, u.title
             ORDER BY open_tasks DESC, u.name ASC
             LIMIT 8',
            [$tenantId]
        );
    }

    protected function taskSelectSql(): string
    {
        return "SELECT t.*,
                    l.name AS list_name, l.color AS list_color, l.icon AS list_icon,
                    au.name AS assignee_name, au.email AS assignee_email, au.title AS assignee_title,
                    cu.name AS creator_name, cu.email AS creator_email
                FROM todos t
                LEFT JOIN todo_lists l ON l.id=t.list_id
                LEFT JOIN users au ON au.id=t.assigned_to_id
                LEFT JOIN users cu ON cu.id=t.created_by_id";
    }

    protected function findTask(int $id, int $tenantId, int $userId): ?array
    {
        return $this->db->one(
            $this->taskSelectSql() . ' WHERE t.id=? AND t.tenant_id=? AND ' . $this->visibleClause('t') . ' LIMIT 1',
            array_merge([$id, $tenantId], $this->visibleArgs($userId))
        );
    }

    protected function visibleClause(string $alias): string
    {
        return "($alias.user_id=? OR $alias.assigned_to_id=? OR $alias.created_by_id=?)";
    }

    protected function visibleArgs(int $userId): array
    {
        return [$userId, $userId, $userId];
    }

    protected function validUserId(int $tenantId, int $userId): ?int
    {
        if ($userId <= 0) return null;
        $id = $this->db->val('SELECT id FROM users WHERE id=? AND tenant_id=? AND is_active=1', [$userId, $tenantId]);
        return $id ? (int)$id : null;
    }

    protected function validListId(int $tenantId, int $listId): ?int
    {
        if ($listId <= 0) return null;
        $id = $this->db->val('SELECT id FROM todo_lists WHERE id=? AND tenant_id=?', [$listId, $tenantId]);
        return $id ? (int)$id : null;
    }

    protected function cleanPriority(string $priority): string
    {
        return in_array($priority, $this->priorities, true) ? $priority : 'medium';
    }

    protected function cleanDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    protected function sanitizeLabels(string $labels): ?string
    {
        $labels = trim(strip_tags(str_replace(["\r", "\n"], ',', $labels)));
        if ($labels === '') return null;
        $parts = array_filter(array_map('trim', explode(',', $labels)));
        $parts = array_slice(array_unique($parts), 0, 8);
        return substr(implode(', ', $parts), 0, 255);
    }

    protected function cleanEstimate(string $value): ?int
    {
        $minutes = (int)$value;
        return $minutes > 0 ? min($minutes, 9999) : null;
    }

    protected function sanitizeColor(string $color): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#6366f1';
    }

    protected function sendTaskEmail(array $task, string $event): array
    {
        $recipientEmail = trim((string)($task['assignee_email'] ?: $task['creator_email'] ?: ''));
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'destinatario invalido'];
        }

        $recipientName = (string)($task['assignee_name'] ?: $task['creator_name'] ?: '');
        $title = (string)$task['title'];
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $url = rtrim($this->app->config['app']['url'] ?? '', '/') . '/t/' . $this->app->tenant->slug . '/todos';
        $priority = $this->priorityLabel((string)$task['priority']);
        $due = $task['due_at'] ? date('d/m/Y H:i', strtotime((string)$task['due_at'])) : 'Sin fecha limite';
        $reminder = $task['reminder_at'] ? date('d/m/Y H:i', strtotime((string)$task['reminder_at'])) : 'Sin recordatorio';
        $creator = htmlspecialchars((string)($task['creator_name'] ?: 'Kydesk'), ENT_QUOTES, 'UTF-8');

        $titles = [
            'created' => 'Nueva tarea asignada',
            'updated' => 'Tarea actualizada',
            'reminder' => 'Recordatorio de tarea',
            'completed' => 'Tarea completada',
        ];
        $heading = $titles[$event] ?? 'Actualizacion de tarea';
        $subject = $heading . ': ' . substr($title, 0, 90);

        $inner = '<p style="margin-top:0">' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . ' en tu workspace.</p>'
            . '<div style="border:1px solid #ececef;border-radius:12px;padding:16px;margin:16px 0;background:#fafafb;">'
            . '<strong style="display:block;font-size:16px;color:#16151b;margin-bottom:8px;">' . $safeTitle . '</strong>'
            . ($task['description'] ? '<p style="margin:0 0 12px;color:#55515f;">' . nl2br(htmlspecialchars((string)$task['description'], ENT_QUOTES, 'UTF-8')) . '</p>' : '')
            . '<p style="margin:0;color:#6b6a78;font-size:14px;">Prioridad: <strong>' . htmlspecialchars($priority, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p style="margin:6px 0 0;color:#6b6a78;font-size:14px;">Vence: <strong>' . htmlspecialchars($due, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p style="margin:6px 0 0;color:#6b6a78;font-size:14px;">Alerta: <strong>' . htmlspecialchars($reminder, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p style="margin:6px 0 0;color:#6b6a78;font-size:14px;">Creada por: <strong>' . $creator . '</strong></p>'
            . '</div>';

        return (new Mailer())->send(
            ['email' => $recipientEmail, 'name' => $recipientName],
            $subject,
            Mailer::template($heading, $inner, 'Abrir tareas', $url),
            null,
            [
                'tags' => [
                    ['name' => 'module', 'value' => 'todos'],
                    ['name' => 'event', 'value' => 'todo_' . preg_replace('/[^a-z0-9_-]/i', '_', $event)],
                    ['name' => 'todo_id', 'value' => (string)(int)$task['id']],
                ],
                'idempotency_key' => 'todo-' . (int)$task['id'] . '-' . $event . '-' . date('YmdHi'),
            ]
        );
    }

    protected function priorityLabel(string $priority): string
    {
        return [
            'urgent' => 'Urgente',
            'high' => 'Alta',
            'medium' => 'Media',
            'low' => 'Baja',
        ][$priority] ?? 'Media';
    }
}
