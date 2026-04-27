<?php
namespace App\Controllers;

use App\Core\Controller;

class CustomFieldController extends Controller
{
    public const TYPES = ['text','textarea','number','date','select','multiselect','checkbox','url','email','phone'];

    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('custom_fields');
        $this->requireCan('custom_fields.view');

        $fields = $this->db->all(
            "SELECT f.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon
             FROM custom_fields f LEFT JOIN ticket_categories c ON c.id = f.category_id
             WHERE f.tenant_id = ? ORDER BY f.sort_order, f.id",
            [$tenant->id]
        );
        $categories = $this->db->all('SELECT * FROM ticket_categories WHERE tenant_id = ? ORDER BY name', [$tenant->id]);

        $this->render('custom_fields/index', [
            'title' => 'Custom Fields',
            'fields' => $fields,
            'categories' => $categories,
            'types' => self::TYPES,
        ]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('custom_fields');
        $this->requireCan('custom_fields.edit');
        $this->validateCsrf();

        $label = trim((string)$this->input('label', ''));
        if ($label === '') { $this->session->flash('error', 'El label es obligatorio.'); $this->redirect('/t/' . $tenant->slug . '/custom-fields'); }

        $type = (string)$this->input('type', 'text');
        if (!in_array($type, self::TYPES, true)) $type = 'text';

        $key = $this->uniqueKey($tenant->id, $this->input('field_key', $label));
        $options = null;
        if (in_array($type, ['select','multiselect'], true)) {
            $raw = trim((string)$this->input('options_raw', ''));
            $opts = array_values(array_filter(array_map('trim', explode("\n", $raw))));
            $options = json_encode($opts, JSON_UNESCAPED_UNICODE);
        }

        $this->db->insert('custom_fields', [
            'tenant_id'         => $tenant->id,
            'category_id'       => ((int)$this->input('category_id', 0)) ?: null,
            'field_key'         => $key,
            'label'             => $label,
            'type'              => $type,
            'options'           => $options,
            'placeholder'       => (string)$this->input('placeholder', '') ?: null,
            'help_text'         => (string)$this->input('help_text', '') ?: null,
            'is_required'       => (int)($this->input('is_required') ? 1 : 0),
            'is_visible_portal' => (int)($this->input('is_visible_portal') ? 1 : 0),
            'sort_order'        => (int)$this->input('sort_order', 0),
            'is_active'         => 1,
        ]);
        $this->session->flash('success', 'Custom field creado.');
        $this->redirect('/t/' . $tenant->slug . '/custom-fields');
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('custom_fields');
        $this->requireCan('custom_fields.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $f = $this->db->one('SELECT * FROM custom_fields WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$f) { $this->redirect('/t/' . $tenant->slug . '/custom-fields'); }

        $type = (string)$this->input('type', $f['type']);
        if (!in_array($type, self::TYPES, true)) $type = $f['type'];
        $options = $f['options'];
        if (in_array($type, ['select','multiselect'], true)) {
            $raw = trim((string)$this->input('options_raw', ''));
            $opts = array_values(array_filter(array_map('trim', explode("\n", $raw))));
            $options = json_encode($opts, JSON_UNESCAPED_UNICODE);
        }

        $this->db->update('custom_fields', [
            'category_id'       => ((int)$this->input('category_id', 0)) ?: null,
            'label'             => trim((string)$this->input('label', $f['label'])),
            'type'              => $type,
            'options'           => $options,
            'placeholder'       => (string)$this->input('placeholder', '') ?: null,
            'help_text'         => (string)$this->input('help_text', '') ?: null,
            'is_required'       => (int)($this->input('is_required') ? 1 : 0),
            'is_visible_portal' => (int)($this->input('is_visible_portal') ? 1 : 0),
            'sort_order'        => (int)$this->input('sort_order', $f['sort_order']),
            'is_active'         => (int)($this->input('is_active') ? 1 : 0),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $this->session->flash('success', 'Custom field actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/custom-fields');
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('custom_fields');
        $this->requireCan('custom_fields.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('ticket_field_values', 'field_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('custom_fields', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Campo eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/custom-fields');
    }

    /**
     * Devuelve los custom fields aplicables a un ticket (por categoría o globales).
     * Usado por TicketController::show / create.
     */
    public static function fieldsFor(int $tenantId, ?int $categoryId, $db): array
    {
        $args = [$tenantId];
        $sql = 'SELECT * FROM custom_fields WHERE tenant_id = ? AND is_active = 1';
        if ($categoryId) {
            $sql .= ' AND (category_id IS NULL OR category_id = ?)';
            $args[] = $categoryId;
        } else {
            $sql .= ' AND category_id IS NULL';
        }
        $sql .= ' ORDER BY sort_order, id';
        return $db->all($sql, $args);
    }

    public static function valuesFor(int $tenantId, int $ticketId, $db): array
    {
        $rows = $db->all('SELECT field_id, value FROM ticket_field_values WHERE tenant_id = ? AND ticket_id = ?', [$tenantId, $ticketId]);
        $map = [];
        foreach ($rows as $r) $map[(int)$r['field_id']] = $r['value'];
        return $map;
    }

    public static function saveValues(int $tenantId, int $ticketId, array $fieldValues, $db): void
    {
        foreach ($fieldValues as $fieldId => $value) {
            $fieldId = (int)$fieldId;
            $val = is_array($value) ? json_encode(array_values($value), JSON_UNESCAPED_UNICODE) : (string)$value;
            $exists = $db->one('SELECT id FROM ticket_field_values WHERE ticket_id=? AND field_id=?', [$ticketId, $fieldId]);
            if ($exists) {
                $db->update('ticket_field_values', ['value' => $val], 'id = :id', ['id' => (int)$exists['id']]);
            } else {
                $db->insert('ticket_field_values', [
                    'tenant_id' => $tenantId,
                    'ticket_id' => $ticketId,
                    'field_id'  => $fieldId,
                    'value'     => $val,
                ]);
            }
        }
    }

    protected function uniqueKey(int $tenantId, string $base): string
    {
        $base = preg_replace('/[^a-z0-9_]+/', '_', strtolower($base));
        $base = trim($base, '_');
        if ($base === '') $base = 'field';
        $key = substr($base, 0, 70);
        $i = 1;
        while ($this->db->val('SELECT id FROM custom_fields WHERE tenant_id=? AND field_key=?', [$tenantId, $key])) {
            $i++;
            $key = substr($base, 0, 70 - strlen((string)$i) - 1) . '_' . $i;
        }
        return $key;
    }
}
