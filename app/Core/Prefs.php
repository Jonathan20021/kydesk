<?php
namespace App\Core;

class Prefs
{
    public const DEFAULTS = [
        'theme' => 'light',
        'accent' => '#7c5cff',
        'density' => 'comfortable',
        'sidebar_mode' => 'expanded',
        'wallpaper' => 'none',
        'default_landing' => 'dashboard',
        'default_ticket_view' => 'list',
        'show_hero' => 1,
        'show_stats' => 1,
        'show_tickets_grid' => 1,
        'show_inbox' => 1,
        'show_team' => 1,
        'show_sla' => 1,
        'show_todos' => 1,
        'notify_desktop' => 0,
        'notify_sound' => 0,
        'notify_email_digest' => 'daily',
        'locale' => 'es',
        'date_format' => 'dmy',
    ];

    public const ACCENT_PRESETS = [
        '#7c5cff' => 'Iris',
        '#3b82f6' => 'Océano',
        '#16a34a' => 'Bosque',
        '#f59e0b' => 'Atardecer',
        '#ec4899' => 'Cereza',
        '#0f172a' => 'Grafito',
    ];

    public const WALLPAPERS = [
        'none' => 'Limpio',
        'grid' => 'Cuadrícula',
        'dots' => 'Puntos',
        'mesh' => 'Aurora',
    ];

    public static function ensureSchema(Database $db): void
    {
        $row = $db->one("SHOW COLUMNS FROM users LIKE 'preferences'");
        if (!$row) {
            // Usamos LONGTEXT para máxima compatibilidad (MariaDB y MySQL<5.7 sin tipo JSON nativo).
            try {
                $db->run("ALTER TABLE users ADD COLUMN preferences LONGTEXT NULL");
            } catch (\Throwable $e) {
                $db->run("ALTER TABLE users ADD COLUMN preferences TEXT NULL");
            }
        }
    }

    public static function get(?array $user): array
    {
        if (!$user) return self::DEFAULTS;
        $raw = $user['preferences'] ?? null;
        $stored = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $stored = $decoded;
        } elseif (is_array($raw)) {
            $stored = $raw;
        }
        $merged = array_merge(self::DEFAULTS, $stored);
        // Normaliza tipos: las flags 0/1 deben ser int, no string ni bool, para que el dashboard las evalúe bien.
        foreach (self::DEFAULTS as $k => $default) {
            if (is_int($default) || is_bool($default)) {
                $merged[$k] = (int)(bool)($merged[$k] ?? 0);
            }
        }
        return $merged;
    }

    public static function save(Database $db, int $userId, array $prefs): void
    {
        self::ensureSchema($db);
        $clean = [];
        foreach (self::DEFAULTS as $k => $default) {
            if (!array_key_exists($k, $prefs)) {
                // Para flags booleanos (int 0/1) un checkbox no marcado no llega en POST → se guarda como 0.
                $clean[$k] = (is_int($default) || is_bool($default)) ? 0 : $default;
                continue;
            }
            $v = $prefs[$k];
            if (is_int($default) || is_bool($default)) $clean[$k] = (int)(bool)$v;
            else $clean[$k] = (string)$v;
        }
        $clean['accent'] = self::sanitizeColor($clean['accent']);
        $clean['theme'] = in_array($clean['theme'], ['light','dark','auto'], true) ? $clean['theme'] : 'light';
        $clean['density'] = in_array($clean['density'], ['compact','comfortable','spacious'], true) ? $clean['density'] : 'comfortable';
        $clean['sidebar_mode'] = in_array($clean['sidebar_mode'], ['expanded','compact'], true) ? $clean['sidebar_mode'] : 'expanded';
        $clean['wallpaper'] = isset(self::WALLPAPERS[$clean['wallpaper']]) ? $clean['wallpaper'] : 'none';
        $clean['default_landing'] = in_array($clean['default_landing'], ['dashboard','tickets','board','todos'], true) ? $clean['default_landing'] : 'dashboard';
        $clean['default_ticket_view'] = in_array($clean['default_ticket_view'], ['list','board'], true) ? $clean['default_ticket_view'] : 'list';
        $clean['notify_email_digest'] = in_array($clean['notify_email_digest'], ['off','daily','weekly'], true) ? $clean['notify_email_digest'] : 'daily';
        $clean['locale'] = in_array($clean['locale'], ['es','en','pt'], true) ? $clean['locale'] : 'es';
        $clean['date_format'] = in_array($clean['date_format'], ['dmy','mdy','ymd'], true) ? $clean['date_format'] : 'dmy';

        $json = json_encode($clean, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('No se pudo serializar las preferencias: ' . json_last_error_msg());
        }
        $db->update('users', ['preferences' => $json], 'id = ?', [$userId]);
    }

    protected static function sanitizeColor(string $color): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : '#7c5cff';
    }

    public static function bodyAttrs(array $prefs): string
    {
        return sprintf(
            'data-theme="%s" data-density="%s" data-sidebar="%s" data-wallpaper="%s"',
            htmlspecialchars($prefs['theme']),
            htmlspecialchars($prefs['density']),
            htmlspecialchars($prefs['sidebar_mode']),
            htmlspecialchars($prefs['wallpaper'])
        );
    }

    public static function styleVars(array $prefs): string
    {
        $accent = self::sanitizeColor($prefs['accent']);
        return sprintf('--accent:%s;--brand-500:%s;', $accent, $accent);
    }
}
