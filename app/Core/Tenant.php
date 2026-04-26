<?php
namespace App\Core;

class Tenant
{
    public int $id;
    public string $slug;
    public string $name;
    public array $data;

    public function __construct(array $row)
    {
        $this->id = (int)$row['id'];
        $this->slug = $row['slug'];
        $this->name = $row['name'];
        $this->data = $row;
    }

    public static function resolve(string $slug): ?self
    {
        $app = Application::get();
        $row = $app->db->one('SELECT * FROM tenants WHERE slug = :s AND is_active = 1 LIMIT 1', ['s' => $slug]);
        return $row ? new self($row) : null;
    }

    public static function find(int $id): ?self
    {
        $app = Application::get();
        $row = $app->db->one('SELECT * FROM tenants WHERE id = :i AND is_active = 1 LIMIT 1', ['i' => $id]);
        return $row ? new self($row) : null;
    }
}
