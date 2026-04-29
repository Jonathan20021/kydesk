<?php
namespace App\Core\Conference;

use App\Core\Jwt;
use App\Core\Tenant;

/**
 * LiveKit provider — STUB para migración futura.
 *
 * Cuando se quiera activar:
 *   1. Configurar livekit_url, livekit_api_key, livekit_api_secret en meeting_settings
 *   2. Cambiar conference_provider = 'livekit' en meeting_settings
 *   3. Implementar embedConfig() para servir el LiveKit Web SDK en lugar de Jitsi IFrame API
 *   4. La firma del access token (HS256 JWT) ya está implementada acá
 *
 * Por ahora si alguien lo selecciona, devuelve config vacío y el frontend hace fallback a Jitsi.
 */
class LiveKitProvider implements Provider
{
    protected array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function name(): string { return 'livekit'; }

    public function createRoom(Tenant $tenant, array $meeting, array $type = []): array
    {
        $existing = $meeting['conference_room_id'] ?? null;
        $roomId = $existing ?: ('kydesk-' . (int)$tenant->id . '-' . substr((string)($meeting['public_token'] ?? bin2hex(random_bytes(8))), 0, 16));
        return [
            'room_id' => $roomId,
            'url'     => null,
            'meta'    => ['provider' => 'livekit', 'pending_implementation' => true],
        ];
    }

    public function joinUrl(Tenant $tenant, array $meeting, array $user): string
    {
        // LiveKit web client URL (placeholder)
        $url = trim((string)($this->settings['livekit_url'] ?? ''));
        if ($url === '') return '';
        $token = $this->generateAccessToken($tenant, $meeting, $user);
        return rtrim($url, '/') . '?token=' . rawurlencode($token);
    }

    public function embedConfig(Tenant $tenant, array $meeting, array $user): array
    {
        // Retorna stub. Frontend detecta y muestra mensaje "LiveKit aún no implementado"
        return [
            'provider' => 'livekit',
            'enabled'  => false,
            'message'  => 'LiveKit estará disponible próximamente. Cambiá a Jitsi en ajustes.',
        ];
    }

    /**
     * Firma un access token de LiveKit (HS256 JWT).
     * Documentación: https://docs.livekit.io/realtime/concepts/authentication/
     */
    protected function generateAccessToken(Tenant $tenant, array $meeting, array $user): string
    {
        $apiKey = (string)($this->settings['livekit_api_key'] ?? '');
        $secret = (string)($this->settings['livekit_api_secret'] ?? '');
        $roomId = (string)($meeting['conference_room_id'] ?? '');
        $isHost = ($user['role'] ?? '') === 'host';
        $now = time();

        $payload = [
            'iss'   => $apiKey,
            'sub'   => 'user-' . md5(($user['email'] ?? 'guest') . $roomId),
            'name'  => (string)($user['name'] ?? 'Invitado'),
            'iat'   => $now,
            'nbf'   => $now,
            'exp'   => $now + 7200,
            'video' => [
                'room'        => $roomId,
                'roomJoin'    => true,
                'canPublish'  => true,
                'canSubscribe'=> true,
                'canPublishData' => true,
                'roomCreate'  => $isHost,
                'roomAdmin'   => $isHost,
                'roomRecord'  => $isHost,
            ],
            'metadata' => json_encode([
                'role'  => $isHost ? 'host' : 'guest',
                'email' => $user['email'] ?? '',
            ]),
        ];
        return Jwt::signHS256($payload, $secret);
    }
}
