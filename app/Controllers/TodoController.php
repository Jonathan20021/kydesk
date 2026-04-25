<?php
namespace App\Controllers;

use App\Core\Controller;

class TodoController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.view');

        $listId = (int)($_GET['list'] ?? 0);
        $showCompleted = !empty($_GET['completed']);

        $lists = $this->db->all('SELECT * FROM todo_lists WHERE tenant_id=? AND user_id=? ORDER BY id', [$tenant->id, $this->auth->userId()]);

        $where = 'tenant_id=? AND user_id=?';
        $args = [$tenant->id, $this->auth->userId()];
        if ($listId) { $where .= ' AND list_id=?'; $args[] = $listId; }
        if (!$showCompleted) $where .= ' AND completed=0';

        $todos = $this->db->all("SELECT * FROM todos WHERE $where ORDER BY completed ASC, FIELD(priority,'urgent','high','medium','low'), created_at DESC", $args);

        $counts = [
            'pending' => (int)$this->db->val('SELECT COUNT(*) FROM todos WHERE tenant_id=? AND user_id=? AND completed=0', [$tenant->id, $this->auth->userId()]),
            'done'    => (int)$this->db->val('SELECT COUNT(*) FROM todos WHERE tenant_id=? AND user_id=? AND completed=1', [$tenant->id, $this->auth->userId()]),
            'today'   => (int)$this->db->val("SELECT COUNT(*) FROM todos WHERE tenant_id=? AND user_id=? AND completed=0 AND DATE(due_at) = CURDATE()", [$tenant->id, $this->auth->userId()]),
        ];

        $this->render('todos/index', [
            'title' => 'Tareas',
            'lists' => $lists, 'todos' => $todos,
            'currentListId' => $listId, 'showCompleted' => $showCompleted,
            'counts' => $counts,
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
            'color' => (string)$this->input('color','#6366f1'),
            'icon' => (string)$this->input('icon','list'),
        ]);
        $this->redirect('/t/' . $tenant->slug . '/todos');
    }

    public function deleteList(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.delete');
        $this->validateCsrf();
        $this->db->delete('todo_lists', 'id=? AND tenant_id=? AND user_id=?', [(int)$params['id'], $tenant->id, $this->auth->userId()]);
        $this->redirect('/t/' . $tenant->slug . '/todos');
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.create');
        $this->validateCsrf();
        $title = trim((string)$this->input('title'));
        if ($title === '') { $this->back(); return; }
        $due = (string)$this->input('due_at','');
        $this->db->insert('todos', [
            'tenant_id' => $tenant->id,
            'user_id' => $this->auth->userId(),
            'list_id' => ((int)$this->input('list_id',0)) ?: null,
            'title' => $title,
            'description' => (string)$this->input('description',''),
            'priority' => (string)$this->input('priority','medium'),
            'due_at' => $due ?: null,
        ]);
        $this->back();
    }

    public function toggle(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $t = $this->db->one('SELECT * FROM todos WHERE id=? AND tenant_id=? AND user_id=?', [$id, $tenant->id, $this->auth->userId()]);
        if (!$t) { $this->back(); return; }
        $now = $t['completed'] ? null : date('Y-m-d H:i:s');
        $this->db->update('todos', ['completed' => $t['completed'] ? 0 : 1, 'completed_at' => $now], 'id=?', ['id' => $id]);
        $this->back();
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('todos.delete');
        $this->validateCsrf();
        $this->db->delete('todos', 'id=? AND tenant_id=? AND user_id=?', [(int)$params['id'], $tenant->id, $this->auth->userId()]);
        $this->back();
    }
}
