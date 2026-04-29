<?php
namespace App\Core\Conference;

use App\Core\Application;
use App\Core\Tenant;

/**
 * Resuelve el provider de conferencia activo para un tenant.
 *
 * Lee meeting_settings para saber cuál usar.
 * Si el provider configurado falla (ej. LiveKit sin keys), hace fallback a Jitsi público.
 */
class ConferenceFactory
{
    /** Cache por tenant_id en proceso. */
    protected static array $cache = [];

    public static function forTenant(Tenant $tenant): Provider
    {
        $tid = $tenant->id;
        if (isset(self::$cache[$tid])) return self::$cache[$tid];

        $settings = self::loadSettings($tenant);
        $providerSlug = strtolower((string)($settings['conference_provider'] ?? 'jitsi'));

        $provider = match ($providerSlug) {
            'livekit' => self::makeLiveKit($settings),
            default   => new JitsiProvider($settings),
        };
        return self::$cache[$tid] = $provider;
    }

    /**
     * ¿La conferencia integrada está habilitada para este tenant?
     * Si no, los meetings caen al comportamiento legacy (meeting_url manual).
     */
    public static function isEnabled(Tenant $tenant): bool
    {
        $settings = self::loadSettings($tenant);
        return (int)($settings['conference_enabled'] ?? 1) === 1;
    }

    public static function clearCache(?int $tenantId = null): void
    {
        if ($tenantId === null) self::$cache = [];
        else unset(self::$cache[$tenantId]);
    }

    protected static function makeLiveKit(array $settings): Provider
    {
        // Si LiveKit no está configurado completamente, fallback a Jitsi
        if (empty($settings['livekit_url']) || empty($settings['livekit_api_key']) || empty($settings['livekit_api_secret'])) {
            return new JitsiProvider($settings);
        }
        return new LiveKitProvider($settings);
    }

    protected static function loadSettings(Tenant $tenant): array
    {
        try {
            $row = Application::get()->db->one(
                'SELECT conference_enabled, conference_provider,
                        jitsi_domain, jitsi_app_id, jitsi_kid, jitsi_app_secret, jitsi_audio_only,
                        livekit_url, livekit_api_key, livekit_api_secret
                 FROM meeting_settings WHERE tenant_id = ?',
                [$tenant->id]
            );
        } catch (\Throwable $e) { $row = null; }
        return $row ?: [];
    }
}
