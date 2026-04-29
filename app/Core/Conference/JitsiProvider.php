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
        $roomId    = (string)($meeting['conference_room_id'] ?? '');
        $audioOnly = !empty($this->settings['jitsi_audio_only']) || (($meeting['location_type'] ?? '') === 'phone');
        $isHost    = ($user['role'] ?? '') === 'host';

        // meet.jit.si (gratis) corta embeds a los 5 min · forzar "abrir en pestaña nueva"
        $isFreeDemoDomain = strpos($this->domain(), 'meet.jit.si') !== false;
        $canEmbed = $this->hasJwt() || !$isFreeDemoDomain;

        // 8x8.vc / JaaS: el roomName del IFrame API debe ser "appId/{roomId}"
        $embedRoomName = $roomId;
        if ($this->isJaaS()) {
            $appId = (string)($this->settings['jitsi_app_id'] ?? '');
            if ($appId !== '') $embedRoomName = $appId . '/' . $roomId;
        }

        // Subject visible dentro del meeting (header de Jitsi)
        $subject = (string)($meeting['conference_subject']
            ?? $meeting['type_name']
            ?? $meeting['subject']
            ?? '');
        if ($subject === '' && !empty($meeting['customer_name'])) {
            $subject = 'Reunión con ' . $meeting['customer_name'];
        }

        // Avatar generado deterministicamente desde el email/nombre
        $avatarSeed = strtolower(trim((string)($user['email'] ?? $user['name'] ?? 'guest')));
        $avatarUrl  = 'https://api.dicebear.com/7.x/initials/svg?seed=' . rawurlencode($avatarSeed)
            . '&backgroundColor=7c5cff,a78bfa,d946ef,0ea5e9,10b981&backgroundType=gradientLinear';

        // Toggles de tenant (defaults conservadores)
        $recordingEnabled     = !empty($this->settings['recording_enabled']);
        $recordingAutoStart   = !empty($this->settings['recording_auto_start']);
        $transcriptionEnabled = !empty($this->settings['transcription_enabled']);
        $lobbyEnabled         = !empty($this->settings['lobby_enabled']);
        $prejoinEnabled       = (int)($this->settings['prejoin_enabled'] ?? 1) === 1;
        $livestreamEnabled    = !empty($this->settings['livestream_enabled']);

        // Override por tipo de reunión
        $typeRecMode    = (string)($meeting['recording_mode']     ?? 'inherit');
        $typeTransMode  = (string)($meeting['transcription_mode'] ?? 'inherit');
        if ($typeRecMode === 'always') $recordingEnabled = true;
        if ($typeRecMode === 'never')  { $recordingEnabled = false; $recordingAutoStart = false; }
        if ($typeRecMode === 'optional') { $recordingEnabled = true; $recordingAutoStart = false; }
        if ($typeTransMode === 'always') $transcriptionEnabled = true;
        if ($typeTransMode === 'never')  $transcriptionEnabled = false;

        // Branding del tenant
        $primaryColor = (string)($this->settings['primary_color'] ?? '#7c5cff');
        $logoUrl      = (string)($this->settings['logo_url'] ?? '');
        $businessName = (string)($this->settings['business_name'] ?? $tenant->name);

        // Toolbar dinámico por rol y configuración
        $toolbarHost = ['microphone','camera','desktop','chat','raisehand','tileview','select-background','settings','hangup','toggle-camera','fullscreen','filmstrip','participants-pane','security','etherpad','help'];
        $toolbarGuest = ['microphone','camera','chat','raisehand','tileview','settings','hangup','toggle-camera','fullscreen','filmstrip'];
        if ($recordingEnabled && $isHost) $toolbarHost[] = 'recording';
        if ($livestreamEnabled && $isHost) $toolbarHost[] = 'livestreaming';
        if ($transcriptionEnabled) $toolbarHost[] = 'closedcaptions';
        $toolbar = $isHost ? $toolbarHost : $toolbarGuest;
        if ($audioOnly) {
            $toolbar = array_values(array_diff($toolbar, ['camera','desktop','select-background','toggle-camera','fullscreen','filmstrip']));
            $toolbar = array_unique(array_merge($toolbar, ['microphone','chat','raisehand','tileview','settings','hangup']));
        }

        $config = [
            'provider'   => 'jitsi',
            'domain'     => $this->domain(),
            'roomName'   => $embedRoomName,
            'subject'    => $subject,
            'jwt'        => $this->hasJwt() ? $this->generateJwt($tenant, $meeting, $user, $roomId) : null,
            'embedMode'  => $canEmbed ? 'iframe' : 'new_tab',
            'joinUrl'    => $this->joinUrl($tenant, $meeting, $user),
            'userInfo'   => [
                'displayName' => (string)($user['name'] ?? ''),
                'email'       => (string)($user['email'] ?? ''),
                'avatarURL'   => $avatarUrl,
            ],
            'configOverwrite' => [
                'subject'              => $subject,
                'prejoinPageEnabled'   => $prejoinEnabled,
                'enableLobbyChat'      => $lobbyEnabled,
                'lobby'                => ['autoKnock' => $lobbyEnabled, 'enableChat' => $lobbyEnabled],
                'startWithVideoMuted'  => $audioOnly,
                'startWithAudioMuted'  => false,
                'disableDeepLinking'   => true,
                'enableWelcomePage'    => false,
                'enableClosePage'      => false,
                'requireDisplayName'   => true,
                'disableProfile'       => false,
                'disableInviteFunctions' => !$isHost,
                'fileRecordingsEnabled'  => $recordingEnabled,
                'liveStreamingEnabled'   => $livestreamEnabled,
                'transcribingEnabled'    => $transcriptionEnabled,
                'transcription' => [
                    'enabled'              => $transcriptionEnabled,
                    'autoTranscribeOnRecord' => $transcriptionEnabled && $recordingEnabled,
                    'useAppLanguage'       => true,
                ],
                'startSilent'          => false,
                'noisyMicDetection'    => true,
                'enableNoAudioDetection' => true,
                'enableTalkWhileMuted'  => true,
                'p2p'                  => ['enabled' => true],
                'analytics'            => ['disabled' => true],
                'disableThirdPartyRequests' => true,
                'startupRoomName'      => $embedRoomName,
                'localRecording'       => ['disable' => false, 'notifyAllParticipants' => true],
                'toolbarButtons'       => $toolbar,
                // Auto-start recording cuando el host entra
                'startRecording'       => ($recordingEnabled && $recordingAutoStart && $isHost) ? ['mode' => 'file'] : false,
            ],
            'interfaceConfigOverwrite' => [
                'SHOW_JITSI_WATERMARK'         => false,
                'SHOW_WATERMARK_FOR_GUESTS'    => false,
                'SHOW_BRAND_WATERMARK'         => !empty($logoUrl),
                'BRAND_WATERMARK_LINK'         => $logoUrl ?: '',
                'SHOW_POWERED_BY'              => false,
                'DEFAULT_BACKGROUND'           => '#0f0d18',
                'DISABLE_VIDEO_BACKGROUND'     => false,
                'DEFAULT_LOCAL_DISPLAY_NAME'   => (string)($user['name'] ?? 'Tú'),
                'DEFAULT_REMOTE_DISPLAY_NAME'  => 'Participante',
                'DISABLE_FOCUS_INDICATOR'      => false,
                'DISABLE_DOMINANT_SPEAKER_INDICATOR' => false,
                'GENERATE_ROOMNAMES_ON_WELCOME_PAGE' => false,
                'JITSI_WATERMARK_LINK'         => '',
                'LANG_DETECTION'               => true,
                'MOBILE_APP_PROMO'             => false,
                'NATIVE_APP_NAME'              => $businessName,
                'PROVIDER_NAME'                => $businessName,
                'SETTINGS_SECTIONS'            => ['devices','language','moderator','profile'],
                'SHOW_CHROME_EXTENSION_BANNER' => false,
                'TOOLBAR_BUTTONS'              => $toolbar,
                'TOOLBAR_ALWAYS_VISIBLE'       => false,
                'VIDEO_LAYOUT_FIT'             => 'both',
                'VIDEO_QUALITY_LABEL_DISABLED' => false,
                'CONNECTION_INDICATOR_DISABLED'=> false,
                'CLOSE_PAGE_GUEST_HINT'        => false,
                'TILE_VIEW_MAX_COLUMNS'        => 5,
                'APP_NAME'                     => $businessName . ' · Reuniones',
            ],
            'role'         => $isHost ? 'host' : 'guest',
            'audioOnly'    => $audioOnly,
            'recording'    => [
                'enabled'    => $recordingEnabled,
                'autoStart'  => $recordingAutoStart && $isHost,
            ],
            'transcription'=> ['enabled' => $transcriptionEnabled],
            'lobby'        => ['enabled' => $lobbyEnabled],
            'branding'     => [
                'name'         => $businessName,
                'primaryColor' => $primaryColor,
                'logoUrl'      => $logoUrl,
            ],
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

    /**
     * Genera JWT con la firma adecuada:
     *   - Si el secret parece PEM (RSA private key) → RS256 + header kid (8x8.vc / JaaS)
     *   - Caso contrario → HS256 con shared secret (Jitsi self-hosted)
     */
    protected function generateJwt(Tenant $tenant, array $meeting, array $user, string $roomId): string
    {
        $appId  = (string)($this->settings['jitsi_app_id'] ?? '');
        $secret = (string)($this->settings['jitsi_app_secret'] ?? '');
        $kid    = (string)($this->settings['jitsi_kid'] ?? '');
        $isHost = ($user['role'] ?? '') === 'host';
        $isJaaS = $this->isJaaS();
        $now = time();

        // Resolución de features (solo el host puede grabar/transcribir/streamear)
        $recordingEnabled     = !empty($this->settings['recording_enabled']);
        $transcriptionEnabled = !empty($this->settings['transcription_enabled']);
        $livestreamEnabled    = !empty($this->settings['livestream_enabled']);
        // Override por tipo
        $typeRecMode    = (string)($meeting['recording_mode']     ?? 'inherit');
        $typeTransMode  = (string)($meeting['transcription_mode'] ?? 'inherit');
        if ($typeRecMode   === 'always') $recordingEnabled = true;
        if ($typeRecMode   === 'never')  $recordingEnabled = false;
        if ($typeTransMode === 'always') $transcriptionEnabled = true;
        if ($typeTransMode === 'never')  $transcriptionEnabled = false;

        // Avatar determinístico
        $avatarSeed = strtolower(trim((string)($user['email'] ?? $user['name'] ?? 'guest')));
        $avatarUrl  = 'https://api.dicebear.com/7.x/initials/svg?seed=' . rawurlencode($avatarSeed);

        $payload = [
            'aud'  => 'jitsi',
            'iss'  => $isJaaS ? 'chat' : $appId,
            'sub'  => $isJaaS ? $appId : $this->domain(),
            'room' => $isJaaS ? '*' : $roomId,
            'exp'  => $now + 7200,
            'nbf'  => $now - 30,
            'context' => [
                'user' => [
                    'name'      => (string)($user['name'] ?? 'Invitado'),
                    'email'     => (string)($user['email'] ?? ''),
                    'avatar'    => $avatarUrl,
                    'moderator' => $isHost ? 'true' : 'false',
                    'id'        => $isJaaS ? ('user-' . md5(($user['email'] ?? 'guest') . $roomId)) : null,
                ],
                'features' => [
                    'recording'        => ($isHost && $recordingEnabled) ? 'true' : 'false',
                    'livestreaming'    => ($isHost && $livestreamEnabled) ? 'true' : 'false',
                    'transcription'    => ($isHost && $transcriptionEnabled) ? 'true' : 'false',
                    'outbound-call'    => 'false',
                    'sip-outbound-call'=> 'false',
                    'screen-sharing'   => 'true',
                    'flip'             => 'true',
                ],
                'room' => [
                    'regex' => false,
                ],
            ],
        ];
        // Limpiar id null si no es JaaS
        if (!$isJaaS) unset($payload['context']['user']['id']);

        if (Jwt::looksLikePem($secret)) {
            $extraHeader = [];
            // JaaS requiere kid = "appId/apiKeyId"
            // Aceptamos cualquiera de estos formatos del usuario:
            //   - "48e94b" (solo la parte de la API Key)
            //   - "vpaas-magic-cookie-.../48e94b" (ID completo, como aparece en el dashboard)
            if ($isJaaS && $kid !== '') {
                $extraHeader['kid'] = (strpos($kid, '/') !== false) ? $kid : $appId . '/' . $kid;
            } elseif ($kid !== '') {
                $extraHeader['kid'] = $kid;
            }
            return Jwt::signRS256($payload, $secret, $extraHeader);
        }
        return Jwt::signHS256($payload, $secret);
    }

    protected function isJaaS(): bool
    {
        return strpos($this->domain(), '8x8.vc') !== false
            || strpos((string)($this->settings['jitsi_app_id'] ?? ''), 'vpaas-magic-cookie-') === 0;
    }
}
