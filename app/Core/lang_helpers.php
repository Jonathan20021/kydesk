<?php
/**
 * Global i18n helpers. Lives in the global namespace (no `namespace` declaration)
 * so the function is callable as bare `__('key')` from any view.
 *
 * Required from App\Core\Application::__construct() after Lang::boot().
 */

if (!function_exists('__')) {
    /**
     * Translate a key. Shorthand: __('landing.nav.pricing').
     * Vars are substituted as {name} placeholders.
     */
    function __(string $key, array $vars = []): string
    {
        return \App\Core\Lang::t($key, $vars);
    }
}

if (!function_exists('__e')) {
    /**
     * Translated + html-escaped. Useful when you want a one-token call
     * inside an attribute or plain-text context: <?= __e('common.login') ?>.
     */
    function __e(string $key, array $vars = []): string
    {
        return htmlspecialchars(\App\Core\Lang::t($key, $vars), ENT_QUOTES, 'UTF-8');
    }
}
