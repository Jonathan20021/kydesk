<?php
namespace App\Controllers\Admin;

class ChangelogController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('settings.view');
        $entries = $this->db->all(
            "SELECT e.*, (SELECT COUNT(*) FROM changelog_items WHERE entry_id = e.id) AS items_count
             FROM changelog_entries e
             ORDER BY e.published_at DESC, e.id DESC"
        );
        $featuredId = (int)$this->db->val("SELECT id FROM changelog_entries WHERE is_featured=1 AND is_published=1 ORDER BY published_at DESC LIMIT 1");
        $this->render('admin/changelog/index', [
            'title' => 'Changelog',
            'pageHeading' => 'Changelog',
            'entries' => $entries,
            'featuredId' => $featuredId,
        ]);
    }

    public function create(): void
    {
        $this->requireCan('settings.edit');
        $this->render('admin/changelog/edit', [
            'title' => 'Nueva entrada',
            'pageHeading' => 'Nueva entrada del changelog',
            'entry' => null,
            'items' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireCan('settings.edit');
        $this->validateCsrf();
        $entryId = $this->db->insert('changelog_entries', $this->collect());
        $this->saveItems($entryId);
        $this->maybeUnsetOtherFeatured($entryId);
        $this->superAuth->log('changelog.create', 'changelog', $entryId);
        $this->session->flash('success', 'Entrada del changelog creada.');
        $this->redirect('/admin/changelog');
    }

    public function edit(array $params): void
    {
        $this->requireCan('settings.view');
        $id = (int)$params['id'];
        $entry = $this->db->one("SELECT * FROM changelog_entries WHERE id=?", [$id]);
        if (!$entry) $this->redirect('/admin/changelog');
        $items = $this->db->all("SELECT * FROM changelog_items WHERE entry_id=? ORDER BY sort_order ASC, id ASC", [$id]);
        $this->render('admin/changelog/edit', [
            'title' => 'Editar entrada',
            'pageHeading' => 'Editar ' . $entry['version'],
            'entry' => $entry,
            'items' => $items,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCan('settings.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('changelog_entries', $this->collect(), 'id = :id', ['id' => $id]);
        // Replace items
        $this->db->delete('changelog_items', 'entry_id = :id', ['id' => $id]);
        $this->saveItems($id);
        $this->maybeUnsetOtherFeatured($id);
        $this->superAuth->log('changelog.update', 'changelog', $id);
        $this->session->flash('success', 'Entrada actualizada.');
        $this->redirect('/admin/changelog');
    }

    public function delete(array $params): void
    {
        $this->requireCan('settings.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('changelog_entries', 'id = :id', ['id' => $id]);
        $this->superAuth->log('changelog.delete', 'changelog', $id);
        $this->session->flash('success', 'Entrada eliminada.');
        $this->redirect('/admin/changelog');
    }

    public function feature(array $params): void
    {
        $this->requireCan('settings.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->run('UPDATE changelog_entries SET is_featured=0 WHERE is_featured=1');
        $this->db->update('changelog_entries', ['is_featured' => 1, 'is_published' => 1], 'id = :id', ['id' => $id]);
        $this->session->flash('success', 'Entrada marcada como destacada (aparece en el hero).');
        $this->redirect('/admin/changelog');
    }

    public function togglePublish(array $params): void
    {
        $this->requireCan('settings.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $cur = $this->db->one('SELECT is_published FROM changelog_entries WHERE id=?', [$id]);
        if (!$cur) $this->redirect('/admin/changelog');
        $this->db->update('changelog_entries', ['is_published' => $cur['is_published'] ? 0 : 1], 'id = :id', ['id' => $id]);
        $this->session->flash('success', $cur['is_published'] ? 'Entrada despublicada.' : 'Entrada publicada.');
        $this->redirect('/admin/changelog');
    }

    protected function collect(): array
    {
        $type = (string)$this->input('release_type', 'minor');
        if (!in_array($type, ['major','minor','patch'], true)) $type = 'minor';
        $publishedAt = (string)$this->input('published_at', '');
        if ($publishedAt === '') $publishedAt = date('Y-m-d H:i:s');
        else {
            // accept "YYYY-MM-DD" or full datetime
            if (strlen($publishedAt) === 10) $publishedAt .= ' 00:00:00';
        }
        return [
            'version' => trim((string)$this->input('version', 'v0.0.0')),
            'release_type' => $type,
            'title' => trim((string)$this->input('title', '')),
            'summary' => (string)$this->input('summary', '') ?: null,
            'hero_pill_label' => (string)$this->input('hero_pill_label', '') ?: null,
            'is_featured' => (int)($this->input('is_featured', 0) ? 1 : 0),
            'is_published' => (int)($this->input('is_published', 1) ? 1 : 0),
            'published_at' => $publishedAt,
            'created_by' => $this->superAuth->id(),
        ];
    }

    protected function saveItems(int $entryId): void
    {
        $types = (array)($_POST['item_type'] ?? []);
        $texts = (array)($_POST['item_text'] ?? []);
        $allowed = ['feature','fix','improvement'];
        $order = 0;
        foreach ($texts as $i => $text) {
            $text = trim((string)$text);
            if ($text === '') continue;
            $type = (string)($types[$i] ?? 'feature');
            if (!in_array($type, $allowed, true)) $type = 'feature';
            $this->db->insert('changelog_items', [
                'entry_id' => $entryId,
                'item_type' => $type,
                'text' => $text,
                'sort_order' => $order++,
            ]);
        }
    }

    protected function maybeUnsetOtherFeatured(int $keepId): void
    {
        $isFeat = (int)$this->db->val('SELECT is_featured FROM changelog_entries WHERE id=?', [$keepId]);
        if ($isFeat === 1) {
            $this->db->run('UPDATE changelog_entries SET is_featured=0 WHERE id<>? AND is_featured=1', [$keepId]);
        }
    }
}
