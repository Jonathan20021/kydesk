<?php
namespace App\Core\Conference;

use App\Core\Tenant;

/**
 * Interfaz de provider de video conferencia.
 *
 * Cualquier provider (Jitsi, LiveKit, Daily, etc.) implementa estos 4 métodos.
 * El controller / vista no sabe nada del provider concreto — pide la info al factory.
 */
interface Provider
{
    /** Identificador corto del provider: 'jitsi' | 'livekit' | etc. */
    public function name(): string;

    /**
     * Crea un room para una reunión nueva. Idempotente: si ya hay room_id, lo reusa.
     * @return array{room_id:string, url:string|null, meta:array}
     */
    public function createRoom(Tenant $tenant, array $meeting, array $type = []): array;

    /**
     * Genera URL de join para un participante.
     * @param array $user ['name'=>..., 'email'=>..., 'role'=>'host'|'guest']
     */
    public function joinUrl(Tenant $tenant, array $meeting, array $user): string;

    /**
     * Configuración para embed JS (Jitsi IFrame API o LiveKit Web SDK).
     * @return array — JSON-serializable, lo consume el frontend
     */
    public function embedConfig(Tenant $tenant, array $meeting, array $user): array;
}
