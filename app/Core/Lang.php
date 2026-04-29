<?php
namespace App\Core;

/**
 * Minimalist i18n. Loads flat dot-key dictionaries from app/Lang/{locale}.php.
 *
 * Locale resolution order (first non-empty wins):
 *   1) ?lang=xx in current request URL
 *   2) $_SESSION['locale']
 *   3) cookie 'kydesk_lang'
 *   4) Accept-Language header (best-effort)
 *   5) default (es)
 *
 * Public API:
 *   Lang::boot()         - call once during bootstrap; resolves + persists locale.
 *   Lang::current()      - 'es' | 'en'
 *   Lang::set($code)     - sets session + cookie; returns normalized code.
 *   Lang::available()    - ['es' => 'Español', 'en' => 'English']
 *   Lang::t($key, $vars) - translate; falls back to default locale, then to key.
 *   __('key', $vars)     - global helper (defined below).
 */
class Lang
{
    public const DEFAULT = 'es';
    public const COOKIE  = 'kydesk_lang';

    /** @var array<string, array<string,string>> */
    protected static array $dicts = [];
    protected static ?string $current = null;

    public static function available(): array
    {
        return [
            'es' => 'Español',
            'en' => 'English',
        ];
    }

    public static function boot(): void
    {
        $resolved = self::resolveFromRequest();
        self::$current = $resolved;
        // Persist if it came from URL/cookie/header so it sticks.
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $resolved;
        }
    }

    public static function current(): string
    {
        if (self::$current === null) self::boot();
        return self::$current ?? self::DEFAULT;
    }

    public static function set(string $code): string
    {
        $code = self::normalize($code);
        self::$current = $code;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $code;
        }
        // 1-year cookie. Path '/' so it works on every URL.
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie(self::COOKIE, $code, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[self::COOKIE] = $code;
        return $code;
    }

    public static function t(string $key, array $vars = []): string
    {
        $code = self::current();
        $val = self::lookup($code, $key);
        if ($val === null && $code !== self::DEFAULT) {
            $val = self::lookup(self::DEFAULT, $key);
        }
        if ($val === null) $val = $key;
        if ($vars) {
            foreach ($vars as $k => $v) {
                $val = str_replace('{' . $k . '}', (string)$v, $val);
            }
        }
        return $val;
    }

    public static function has(string $key): bool
    {
        return self::lookup(self::current(), $key) !== null
            || self::lookup(self::DEFAULT, $key) !== null;
    }

    /** Normalize 'es-ES', 'EN_US', etc. to a supported code. */
    public static function normalize(string $code): string
    {
        $code = strtolower(substr(trim($code), 0, 2));
        return array_key_exists($code, self::available()) ? $code : self::DEFAULT;
    }

    public static function htmlLang(): string
    {
        return self::current();
    }

    /**
     * Build the same URL but with ?lang=xx swapped/appended.
     * Useful for the language switcher; no redirect logic needed.
     */
    public static function switchUrl(string $code): string
    {
        $code = self::normalize($code);
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $parts = parse_url($uri);
        $path  = $parts['path'] ?? '/';
        $query = [];
        if (!empty($parts['query'])) parse_str($parts['query'], $query);
        $query['lang'] = $code;
        return $path . '?' . http_build_query($query);
    }

    // --- internals -------------------------------------------------------

    protected static function lookup(string $locale, string $key): ?string
    {
        $dict = self::load($locale);
        return $dict[$key] ?? null;
    }

    /** @return array<string,string> */
    protected static function load(string $locale): array
    {
        if (isset(self::$dicts[$locale])) return self::$dicts[$locale];
        $file = APP_PATH . '/Lang/' . $locale . '.php';
        if (is_file($file)) {
            $data = require $file;
            self::$dicts[$locale] = is_array($data) ? $data : [];
        } else {
            self::$dicts[$locale] = [];
        }
        return self::$dicts[$locale];
    }

    protected static function resolveFromRequest(): string
    {
        // 1. ?lang=xx
        if (!empty($_GET['lang'])) {
            $c = self::normalize((string)$_GET['lang']);
            // Persist cookie immediately so the next page load is sticky.
            $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            @setcookie(self::COOKIE, $c, [
                'expires'  => time() + 60 * 60 * 24 * 365,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
            $_COOKIE[self::COOKIE] = $c;
            return $c;
        }
        // 2. session
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['locale'])) {
            return self::normalize((string)$_SESSION['locale']);
        }
        // 3. cookie
        if (!empty($_COOKIE[self::COOKIE])) {
            return self::normalize((string)$_COOKIE[self::COOKIE]);
        }
        // 4. Accept-Language
        $hdr = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if ($hdr) {
            foreach (explode(',', $hdr) as $part) {
                $code = trim(explode(';', $part)[0] ?? '');
                if ($code === '') continue;
                $code = self::normalize($code);
                if (array_key_exists($code, self::available())) return $code;
            }
        }
        return self::DEFAULT;
    }
}

// Note: the global __() helper lives in app/Core/lang_helpers.php so it
// stays in the global namespace. It's required from Application::__construct().
