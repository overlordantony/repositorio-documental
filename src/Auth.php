<?php
// ============================================================
// src/Auth.php — Autenticación y control de acceso
// ============================================================

class Auth
{
    // Permisos por rol
    private static array $permisos = [
        'superadmin' => [
            'documentos.ver', 'documentos.subir', 'documentos.eliminar',
            'usuarios.ver',   'usuarios.crear',   'usuarios.editar', 'usuarios.eliminar',
            'categorias.ver', 'categorias.crear',  'categorias.editar', 'categorias.eliminar',
            'dependencias.ver','dependencias.crear','dependencias.editar','dependencias.eliminar',
            'log.ver',
        ],
        'editor' => [
            'documentos.ver', 'documentos.subir',
            'categorias.ver', 'dependencias.ver',
        ],
        'viewer' => [
            'documentos.ver',
            'categorias.ver', 'dependencias.ver',
        ],
    ];

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 7200,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();

            // Regenerar ID periódicamente para evitar fixation
            if (!isset($_SESSION['_created'])) {
                $_SESSION['_created'] = time();
            } elseif (time() - $_SESSION['_created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['_created'] = time();
            }
        }
    }

    public static function login(string $usuario, string $password): bool
    {
        $user = Database::row(
            'SELECT * FROM usuarios WHERE usuario = ? AND activo = 1 LIMIT 1',
            [$usuario]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Logger::write('login.fallido', 'usuarios', null, "Intento fallido: $usuario");
            return false;
        }

        // Actualizar último acceso
        Database::query(
            'UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?',
            [$user['id']]
        );

        // Guardar en sesión (sin datos sensibles)
        $_SESSION['auth'] = [
            'id'     => $user['id'],
            'nombre' => $user['nombre'],
            'usuario'=> $user['usuario'],
            'email'  => $user['email'],
            'rol'    => $user['rol'],
        ];

        Logger::write('login.ok', 'usuarios', $user['id'], "Acceso: {$user['usuario']}");
        return true;
    }

    public static function logout(): void
    {
        Logger::write('logout', 'usuarios', self::id());
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['auth']['id']);
    }

    /** Redirigir al login si no está autenticado */
    public static function require(): void
    {
        self::startSession();
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/admin/');
            exit;
        }
    }

    /** Verificar permiso puntual */
    public static function can(string $permiso): bool
    {
        if (!self::check()) return false;
        $rol = $_SESSION['auth']['rol'] ?? '';
        return in_array($permiso, self::$permisos[$rol] ?? [], true);
    }

    /** Abortar si no tiene permiso */
    public static function requirePermiso(string $permiso): void
    {
        if (!self::can($permiso)) {
            http_response_code(403);
            include TPL_PATH . '/error_403.php';
            exit;
        }
    }

    // Getters de datos del usuario en sesión
    public static function id(): ?int     { return $_SESSION['auth']['id']     ?? null; }
    public static function nombre(): string { return $_SESSION['auth']['nombre'] ?? ''; }
    public static function rol(): string    { return $_SESSION['auth']['rol']    ?? ''; }
    public static function email(): string  { return $_SESSION['auth']['email']  ?? ''; }
    public static function usuario(): string{ return $_SESSION['auth']['usuario']?? ''; }

    public static function esSuperadmin(): bool { return self::rol() === 'superadmin'; }
}
