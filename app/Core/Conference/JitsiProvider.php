<?php
namespace App\Core\Conference;

use App\Core\Application;
use App\Core\Jwt;
use App\Core\Tenant;

/**
 * Jitsi Meet provider.
 *
 * Funciona contra:
 *   · meet.jit.si (público · gratis · sin JWT)
 *   · 8x8.vc (producción · requiere app_id + app_secret · genera JWT)
 *   · self-hosted Jitsi (cualquier dominio · JWT opcional)
 *
 * Room ID = "Kydesk{tenantId}{firstHexChars}" — derivado del public_token (cripto-seguro).
 * URL: https://{domain}/{roomId}[?jwt=...]
 */
class JitsiProvider implements Provider
{
    protected array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function name(): string { return 'jitsi'; }

    public function createRoom(Tenant $tenant, array $meeting, array $type = []): array
    {
        $existing = $meeting['conference_room_id'] ?? null;
        if ($existing) {
            return [
                'room_id' => $existing,
                'url'     => $this->roomUrl($existing),
                'meta'    => ['domain' => $this->domain()],
            ];
        }

        // Determinístico desde public_token (sin colisiones · sin DB extra)
        $token = (string)($meeting['public_token'] ?? bin2hex(random_bytes(8)));
        $roomId = 'Kydesk' . ((int)$tenant->id) . substr(preg_replace('/[^a-zA-Z0-9]/', '', $token), 0, 24);

        return [
            'room_id' => $roomId,
            'url'     => $this->roomUrl($roomId),
            'meta'    => ['domain' => $this->domain()],
        ];
    }

    public function joinUrl(Tenant $tenant, array $meeting, array $user): string
    {
        $roomId = (string)($meeting['conference_room_id'] ?? '');
        if ($roomId === '') return '';

        $url = $this->roomUrl($roomId);

        // 8x8.vc / self-hosted con JWT: agregamos token
        if ($this->hasJwt()) {
            $jwt = $this->generateJwt($tenant, $meeting, $user, $roomId);
            $sep = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $sep . 'jwt=' . rawurlencode($jwt);
        }

        // Display name como hash
        $name = trim((string)($user['name'] ?? ''));
        if ($name !== '') {
            $sep = (strpos($url, '#') === false) ? '#' : '&';
            $url .= $sep . 'userInfo.displayName="' . rawurlencode($name) . '"';
            if (!empty($user['email'])) $url .= '&userInfo.email="' . rawurlencode($user['email']) . '"';
        }
        return $url;
    }

    public function embedConfig(Tenant $tenant, array $meeting, array $user): array
    {
        $roomId = (string)($meeting['conference_room_id'] ?? '');
        $audioOnly = !empty($this->settings['jitsi_audio_only']) || (($meeting['location_type'] ?? '') === 'phone');
        $isHost = ($user['role'] ?? '') === 'host';

        $config = [
            'provider'  => 'jitsi',
            'domain'    => $this->domain(),
            'roomName'  => $roomId,
            'jwt'       => $this->hasJwt() ? $this->generateJwt($tenant, $meeting, $user, $roomId) : null,
            'userInfo'  => [
                'displayName' => $user['name'] ?? '',
                'email'       => $user['email'] ?? '',
            ],
            'configOverwrite' => [
                'prejoinPageEnabled'  => true,
                'startWithVideoMuted' => $audioOnly,
                'startWithAudioMuted' => false,
                'disableDeepLinking'  => true,
                'enableWelcomePage'   => false,
                'requireDisplayName'  => true,
            ],
            'interfaceConfigOverwrite' => [
                'SHOW_JITSI_WATERMARK'           => false,
                'SHOW_BRAND_WATERMARK'           => false,
                'SHOW_POWERED_BY'                => false,
                'DEFAULT_BACKGROUND'             => '#0f0d18',
                'DISABLE_VIDEO_BACKGROUND'       => false,
                'TOOLBAR_BUTTONS' => $audioOnly
                    ? ['microphone','hangup','chat','raisehand','tileview','settings','toggle-camera']
                    : ['microphone','camera','desktop','fullscreen','fodeviceselection','hangup','profile','chat','recording','livestreaming','etherpad','raisehand','videoquality','tileview','select-background','help','settings'],
            ],
            'role'    => $isHost ? 'host' : 'guest',
            'audioOnly' => $audioOnly,
        ];
        return $config;
    }

    /* ─────────── Helpers ─────────── */

    protected function domain(): string
    {
        return trim((string)($this->settings['jitsi_domain'] ?? 'meet.jit.si')) ?: 'meet.jit.si';
    }

    protected function hasJwt(): bool
    {
        return !empty($this->settings['jitsi_app_id']) && !empty($this->settings['jitsi_app_secret']);
    }

    protected function roomUrl(string $roomId): string
    {
        $domain = $this->domain();
        // 8x8.vc requiere prefijar el app_id en la ruta del room
        $path = $roomId;
        if ($this->hasJwt() && strpos($domain, '8x8.vc') !== false) {
            $path = (string)$this->settings['jitsi_app_id'] . '/' . $roomId;
        }
        return 'https://' . $domain . '/' . $path;
    }

    protected function generateJwt(Tenant $tenant, array $meeting, array $user, string $roomId): string
    {
        $appId = (string)($this->settings['jitsi_app_id'] ?? '');
        $secret = (string)($this->settings['jitsi_app_secret'] ?? '');
        $isHost = ($user['role'] ?? '') === 'host';
        $now = time();

        $payload = [
            'aud'  => 'jitsi',
            'iss'  => $appId,
            'sub'  => strpos($this->domain(), '8x8.vc') !== false ? $appId : $this->domain(),
            'room' => $roomId,
            'exp'  => $now + 7200,
            'nbf'  => $now - 30,
            'context' => [
                'user' => [
                    'name'      => (string)($user['name'] ?? 'Invitado'),
                    'email'     => (string)($user['email'] ?? ''),
                    'moderator' => $isHost ? 'true' : 'false',
                ],
                'features' => [
                    'recording'      => $isHost ? 'true' : 'false',
                    'livestreaming'  => $isHost ? 'true' : 'false',
                    'transcription'  => $isHost ? 'true' : 'false',
                    'outbound-call'  => 'false',
                ],
            ],
        ];
        return Jwt::signHS256($payload, $secret);
    }
}
