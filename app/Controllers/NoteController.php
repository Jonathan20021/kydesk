<?php
namespace App\Controllers;

use App\Core\Controller;

class NoteController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('notes.view');
        $q = trim((string)($_GET['q'] ?? ''));
        $tag = trim((string)($_GET['tag'] ?? ''));

        $where = 'tenant_id=? AND user_id=?';
        $args = [$tenant->id, $this->auth->userId()];
        if ($q !== '')  { $where .= ' AND (title LIKE ? OR body LIKE ?)'; $qq = "%$q%"; $args[] = $qq; $args[] = $qq; }
        if ($tag !== '') { $where .= ' AND tags LIKE ?'; $args[] = "%$tag%"; }

        $notes = $this->db->all("SELECT * FROM notes WHERE $where ORDER BY pinned DESC, updated_at DESC", $args);

        $allTags = [];
        foreach ($notes as $n) {
            foreach (array_filter(array_map('trim', explode(',', (string)$n['tags']))) as $t) {
                $allTags[$t] = ($allTags[$t] ?? 0) + 1;
            }
        }

        $this->render('notes/index', [
            'title' => 'Notas',
            'notes' => $notes,
            'q' => $q, 'tag' => $tag,
            'allTags' => $allTags,
        ]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('notes.create');
        $this->validateCsrf();
        $title = trim((string)$this->input('title','Nota sin título'));
        if ($title === '') $title = 'Nota sin título';
        $this->db->insert('notes', [
            'tenant_id' => $tenant->id,
            'user_id' => $this->auth->userId(),
            'title' => $title,
            'body' => (string)$this->input('body',''),
            'color' => (string)$this->input('color','#fde68a'),
            'pinned' => (int)($this->input('pinned',0) ? 1 : 0),
            'tags' => (string)$this->input('tags',''),
        ]);
        $this->session->flash('success','Nota creada.');
        $this->redirect('/t/' . $tenant->slug . '/notes');
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('notes.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];

        $data = [];
        foreach (['title','body','color','tags'] as $f) {
            $v = $this->input($f,null);
            if ($v !== null) $data[$f] = $v;
        }
        if ($this->input('toggle_pin',null) !== null) {
            $n = $this->db->one('SELECT pinned FROM notes WHERE id=? AND tenant_id=? AND user_id=?', [$id, $tenant->id, $this->auth->userId()]);
            if ($n) $data['pinned'] = (int)$n['pinned'] ? 0 : 1;
        }
        if ($data) {
            $this->db->update('notes', $data, 'id=? AND tenant_id=? AND user_id=?', [$id, $tenant->id, $this->auth->userId()]);
        }
        $this->redirect('/t/' . $tenant->slug . '/notes');
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireCan('notes.delete');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('notes', 'id=? AND tenant_id=? AND user_id=?', [$id, $tenant->id, $this->auth->userId()]);
        $this->session->flash('success','Nota eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/notes');
    }
}
