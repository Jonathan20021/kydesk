<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Lang;

/**
 * Endpoint behind the language switcher. Persists the chosen locale
 * (session + 1y cookie) and redirects back to the previous page so the
 * switcher works from anywhere — landing, public portals, app panels.
 *
 * GET  /lang/{code}        — sets locale, 302 back to Referer
 * POST /lang/{code}?to=X   — same, but prefers the explicit ?to= URL
 *                            (used by forms when Referer might be missing)
 */
class LangController extends Controller
{
    public function set(array $params): void
    {
        $code = Lang::set((string)($params['code'] ?? ''));

        // Where to bounce back. Allow only same-origin paths to avoid
        // open-redirects via crafted ?to=.
        $to = $_GET['to'] ?? $_POST['to'] ?? ($_SERVER['HTTP_REFERER'] ?? '/');
        if (!$this->isSafeRedirect($to)) {
            // Fall back to the app root with its configured base path applied.
            $this->redirect('/');
            return;
        }
        // $to is already an absolute-from-host URI (it came from REQUEST_URI
        // or Referer), so it already includes the app base. Don't pass it
        // through Controller::redirect() — that would prefix the base again
        // and produce /kyros-helpdesk/kyros-helpdesk/.
        header('Location: ' . $to);
        exit;
    }

    /**
     * Accept absolute same-origin URLs and relative paths only.
     */
    private function isSafeRedirect(string $url): bool
    {
        if ($url === '') return false;
        // Block protocol-relative URLs (`//evil.com/...`) and the Windows
        // variant (`/\evil.com`) — both can escape origin.
        if (strlen($url) >= 2 && $url[0] === '/' && ($url[1] === '/' || $url[1] === '\\')) {
            return false;
        }
        // Same-origin relative path is fine.
        if ($url[0] === '/') return true;
        // Otherwise it must match our own host.
        $parts = parse_url($url);
        if (empty($parts['host'])) return false;
        $appUrl = parse_url($this->app->config['app']['url'] ?? '', PHP_URL_HOST);
        return $appUrl !== null && strcasecmp($parts['host'], $appUrl) === 0;
    }
}
