<?php
// ============================================================
// src/Logger.php — Log de actividad en BD
// ============================================================

class Logger
{
    public static function write(
        string   $accion,
        ?string  $entidad    = null,
        ?int     $entidad_id = null,
        ?string  $detalle    = null
    ): void {
        try {
            $usuario_id = $_SESSION['auth']['id'] ?? null;
            $ip = self::ip();

            Database::query(
                'INSERT INTO log_actividad (usuario_id, accion, entidad, entidad_id, detalle, ip)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$usuario_id, $accion, $entidad, $entidad_id, $detalle, $ip]
            );
        } catch (Throwable) {
            // El log nunca debe interrumpir el flujo principal
        }
    }

    public static function recientes(int $limite = 50): array
    {
        return Database::all(
            'SELECT l.*, u.nombre AS usuario_nombre, u.usuario AS usuario_login
             FROM log_actividad l
             LEFT JOIN usuarios u ON l.usuario_id = u.id
             ORDER BY l.creado_en DESC
             LIMIT ?',
            [$limite]
        );
    }

    private static function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                return explode(',', $_SERVER[$k])[0];
            }
        }
        return '0.0.0.0';
    }
}
