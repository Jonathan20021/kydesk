<?php
namespace App\Core;

/**
 * Lee los datos bancarios desde saas_settings.
 * Centraliza el acceso para que todas las vistas (developer, tenant, admin)
 * muestren la misma información.
 */
class BankInfo
{
    public static function all(): array
    {
        $db = Application::get()->db ?? null;
        $defaults = [
            'bank_name' => 'Banco Popular Dominicana',
            'bank_account_type' => 'Corriente',
            'bank_account_number' => '849693106',
            'bank_id_number' => '402-3417388-4',
            'bank_account_holder' => 'Kyros RD',
            'bank_currency' => 'DOP',
            'billing_approval_email' => 'jonathansandoval@kyrosrd.com',
            'payment_proof_required' => '1',
            'payment_max_file_mb' => '10',
        ];
        if (!$db) return $defaults;
        try {
            $rows = $db->all("SELECT `key`, `value` FROM saas_settings WHERE `key` IN ('" . implode("','", array_keys($defaults)) . "')");
        } catch (\Throwable $e) { return $defaults; }
        $out = $defaults;
        foreach ($rows as $r) {
            if ($r['value'] !== '') $out[$r['key']] = $r['value'];
        }
        return $out;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }
}
