<?php
namespace App\Core;

class Session
{
    public function __construct(protected array $cfg) {}

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name($this->cfg['name']);
        session_set_cookie_params([
            'lifetime' => $this->cfg['lifetime'],
            'path'     => '/',
            'secure'   => $this->cfg['secure'],
            'httponly' => $this->cfg['httponly'],
            'samesite' => $this->cfg['samesite'],
        ]);
        session_start();
    }

    public function put(string $k, $v): void   { $_SESSION[$k] = $v; }
    public function get(string $k, $d = null) { return $_SESSION[$k] ?? $d; }
    public function has(string $k): bool       { return array_key_exists($k, $_SESSION); }
    public function forget(string $k): void    { unset($_SESSION[$k]); }
    public function flash(string $k, $v = null)
    {
        if ($v === null) {
            $val = $_SESSION['_flash'][$k] ?? null;
            unset($_SESSION['_flash'][$k]);
            return $val;
        }
        $_SESSION['_flash'][$k] = $v;
        return null;
    }
    public function regenerate(): void { session_regenerate_id(true); }
    public function destroy(): void    { $_SESSION = []; session_destroy(); }
}
