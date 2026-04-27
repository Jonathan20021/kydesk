<?php
namespace App\Core;

class Helpers
{
    public static function slug(string $t): string
    {
        $t = strtolower(trim($t));
        $t = preg_replace('/[^a-z0-9\s-]/', '', $t);
        $t = preg_replace('/[\s-]+/', '-', $t);
        return trim($t, '-');
    }

    /**
     * Crear o actualizar un contacto a partir de un solicitante de ticket.
     * Devuelve el id del contacto.
     */
    public static function upsertContact(int $tenantId, ?int $companyId, string $name, string $email, ?string $phone = null): ?int
    {
        $email = trim($email);
        $name = trim($name);
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) return null;
        $app = Application::get();
        $db = $app->db;

        try {
            $existing = $db->one('SELECT id, company_id, name, phone FROM contacts WHERE tenant_id=? AND LOWER(email)=? LIMIT 1', [$tenantId, strtolower($email)]);
            if ($existing) {
                $update = [];
                if (!$existing['company_id'] && $companyId) $update['company_id'] = $companyId;
                if (empty($existing['phone']) && !empty($phone)) $update['phone'] = $phone;
                if (empty($existing['name']) || strtolower($existing['name']) === strtolower($email)) $update['name'] = $name;
                if ($update) $db->update('contacts', $update, 'id = :id', ['id' => (int)$existing['id']]);
                return (int)$existing['id'];
            }
            return (int)$db->insert('contacts', [
                'tenant_id'  => $tenantId,
                'company_id' => $companyId,
                'name'       => $name,
                'email'      => $email,
                'phone'      => $phone ?: null,
            ]);
        } catch (\Throwable $e) { return null; }
    }

    /**
     * Match a requester email to a company in the tenant.
     * Strategy:
     *   1) Match against contacts.email (exact)
     *   2) Match domain (after @) against companies.website
     *   3) Match domain against existing contacts in the same domain
     */
    public static function findCompanyByEmail(int $tenantId, ?string $email): ?int
    {
        $email = trim((string)$email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return null;
        $app = Application::get();
        $db = $app->db;

        // Skip generic providers
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        if ($domain === '') return null;
        $generics = ['gmail.com','yahoo.com','hotmail.com','outlook.com','live.com','icloud.com','me.com','aol.com','proton.me','protonmail.com','msn.com','ymail.com','mail.com','gmx.com','zoho.com'];

        // 1) Exact contact match
        try {
            $cid = $db->val('SELECT company_id FROM contacts WHERE tenant_id=? AND LOWER(email)=? AND company_id IS NOT NULL LIMIT 1', [$tenantId, strtolower($email)]);
            if ($cid) return (int)$cid;
        } catch (\Throwable $e) {}

        if (in_array($domain, $generics, true)) return null;

        // 2) Match domain against company website
        try {
            $cid = $db->val(
                'SELECT id FROM companies WHERE tenant_id=? AND website LIKE ? LIMIT 1',
                [$tenantId, '%' . $domain . '%']
            );
            if ($cid) return (int)$cid;
        } catch (\Throwable $e) {}

        // 3) Match domain against any existing contact email in this tenant
        try {
            $cid = $db->val(
                'SELECT company_id FROM contacts WHERE tenant_id=? AND email LIKE ? AND company_id IS NOT NULL LIMIT 1',
                [$tenantId, '%@' . $domain]
            );
            if ($cid) return (int)$cid;
        } catch (\Throwable $e) {}

        return null;
    }

    public static function ago(string $datetime): string
    {
        $ts = strtotime($datetime);
        if (!$ts) return '';
        $diff = time() - $ts;
        if ($diff < 60) return 'hace unos segundos';
        if ($diff < 3600) return 'hace ' . floor($diff/60) . ' min';
        if ($diff < 86400) return 'hace ' . floor($diff/3600) . ' h';
        if ($diff < 604800) return 'hace ' . floor($diff/86400) . ' d';
        return date('d/m/Y', $ts);
    }

    public static function randomCode(int $len = 10): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet)-1)];
        }
        return $out;
    }

    public static function ticketCode(int $tenantId, int $id): string
    {
        return 'TK-' . str_pad((string)$tenantId, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
    }

    public static function colorFor(string $s): string
    {
        $palette = ['#6366f1','#8b5cf6','#ec4899','#f43f5e','#f59e0b','#10b981','#14b8a6','#0ea5e9','#3b82f6','#22c55e'];
        $sum = 0;
        for ($i = 0; $i < strlen($s); $i++) $sum += ord($s[$i]);
        return $palette[$sum % count($palette)];
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $out = '';
        foreach ($parts as $p) { if ($p !== '') $out .= strtoupper(mb_substr($p, 0, 1)); if (mb_strlen($out) >= 2) break; }
        return $out ?: 'U';
    }

    public static function priorityBadge(string $p): string
    {
        $labels = ['low'=>'Baja','medium'=>'Media','high'=>'Alta','urgent'=>'Urgente'];
        $lbl = $labels[$p] ?? ucfirst($p);
        return '<span class="status-pill priority-' . $p . '">' . $lbl . '</span>';
    }

    public static function statusBadge(string $s): string
    {
        $map = [
            'open' => ['Abierto','status-open'],
            'in_progress' => ['En progreso','status-progress'],
            'on_hold' => ['En espera','status-hold'],
            'resolved' => ['Resuelto','status-resolved'],
            'closed' => ['Cerrado','status-closed'],
        ];
        [$l, $c] = $map[$s] ?? [ucfirst($s), 'status-hold'];
        return '<span class="status-pill ' . $c . '">' . $l . '</span>';
    }
}
