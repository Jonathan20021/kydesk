<?php
namespace App\Core;

/**
 * Mini-implementación de JWT HS256.
 * Solo lo necesario para firmar tokens de Jitsi (8x8.vc) y LiveKit en el futuro.
 */
class Jwt
{
    /**
     * Firma un payload con HS256.
     * @param array  $payload  Claims (iss, sub, aud, exp, etc.)
     * @param string $secret   Shared secret
     * @return string  Token JWT compacto
     */
    public static function signHS256(array $payload, string $secret): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $segments = [
            self::base64url(json_encode($header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            self::base64url(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
        ];
        $signing = implode('.', $segments);
        $sig = hash_hmac('sha256', $signing, $secret, true);
        $segments[] = self::base64url($sig);
        return implode('.', $segments);
    }

    protected static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
