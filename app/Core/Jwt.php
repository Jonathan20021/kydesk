<?php
namespace App\Core;

/**
 * Implementación mínima de JWT con HS256 + RS256.
 *
 *   HS256: shared secret (Jitsi self-hosted, LiveKit, custom).
 *   RS256: clave privada RSA (Jitsi as a Service / 8x8.vc).
 */
class Jwt
{
    /** Firma un payload con HS256 (shared secret). */
    public static function signHS256(array $payload, string $secret, array $extraHeader = []): string
    {
        $header = array_merge(['typ' => 'JWT', 'alg' => 'HS256'], $extraHeader);
        $segments = [
            self::base64url(json_encode($header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            self::base64url(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
        ];
        $signing = implode('.', $segments);
        $sig = hash_hmac('sha256', $signing, $secret, true);
        $segments[] = self::base64url($sig);
        return implode('.', $segments);
    }

    /**
     * Firma un payload con RS256 (clave privada RSA en formato PEM).
     * Requerido para 8x8.vc (JaaS).
     *
     * @param array  $payload      Claims
     * @param string $privateKey   Contenido del archivo .pk (PEM, BEGIN PRIVATE KEY...)
     * @param array  $extraHeader  ['kid' => 'appId/apiKeyId'] para JaaS
     * @throws \RuntimeException si openssl no puede usar la clave
     */
    public static function signRS256(array $payload, string $privateKey, array $extraHeader = []): string
    {
        if (!function_exists('openssl_sign')) {
            throw new \RuntimeException('openssl extension no disponible');
        }
        $header = array_merge(['typ' => 'JWT', 'alg' => 'RS256'], $extraHeader);
        $segments = [
            self::base64url(json_encode($header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            self::base64url(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
        ];
        $signing = implode('.', $segments);

        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new \RuntimeException('Clave privada RSA inválida: ' . openssl_error_string());
        }
        $sig = '';
        $ok = openssl_sign($signing, $sig, $key, OPENSSL_ALGO_SHA256);
        if (PHP_VERSION_ID < 80000) { @openssl_pkey_free($key); }
        if (!$ok) {
            throw new \RuntimeException('Falló la firma RS256: ' . openssl_error_string());
        }
        $segments[] = self::base64url($sig);
        return implode('.', $segments);
    }

    /** Detecta si el "secret" es una clave PEM RSA (BEGIN PRIVATE KEY). */
    public static function looksLikePem(string $secret): bool
    {
        return str_contains($secret, 'BEGIN PRIVATE KEY') || str_contains($secret, 'BEGIN RSA PRIVATE KEY');
    }

    protected static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
