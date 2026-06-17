<?php
// ============================================================
// src/User.php — Modelo de usuarios admin
// ============================================================

class User
{
    public static function all(): array
    {
        return Database::all(
            "SELECT u.*, c.nombre AS creado_por_nombre
             FROM usuarios u
             LEFT JOIN usuarios c ON u.creado_por = c.id
             ORDER BY u.creado_en DESC"
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::row('SELECT * FROM usuarios WHERE id = ?', [$id]);
    }

    public static function crear(array $data): array
    {
        // Validaciones
        if (empty($data['nombre']) || empty($data['usuario']) || empty($data['email']) || empty($data['password'])) {
            return ['ok' => false, 'msg' => 'Todos los campos son obligatorios.'];
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Email no válido.'];
        }
        if (strlen($data['password']) < 8) {
            return ['ok' => false, 'msg' => 'La contraseña debe tener al menos 8 caracteres.'];
        }
        if (!in_array($data['rol'], ['superadmin','editor','viewer'], true)) {
            return ['ok' => false, 'msg' => 'Rol no válido.'];
        }

        // Verificar duplicados
        $existe = Database::scalar(
            'SELECT COUNT(*) FROM usuarios WHERE usuario = ? OR email = ?',
            [$data['usuario'], $data['email']]
        );
        if ($existe > 0) {
            return ['ok' => false, 'msg' => 'El usuario o email ya existe.'];
        }

        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        Database::query(
            "INSERT INTO usuarios (nombre, usuario, email, password_hash, rol, creado_por)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$data['nombre'], $data['usuario'], $data['email'], $hash, $data['rol'], Auth::id()]
        );

        $newId = (int) Database::lastId();
        Logger::write('usuario.crear', 'usuarios', $newId, $data['usuario']);
        return ['ok' => true, 'msg' => 'Usuario creado correctamente.'];
    }

    public static function editar(int $id, array $data): array
    {
        if (empty($data['nombre']) || empty($data['email'])) {
            return ['ok' => false, 'msg' => 'Nombre y email son obligatorios.'];
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Email no válido.'];
        }

        // Verificar duplicado de email en otro usuario
        $existe = Database::scalar(
            'SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?',
            [$data['email'], $id]
        );
        if ($existe > 0) {
            return ['ok' => false, 'msg' => 'El email ya está en uso por otro usuario.'];
        }

        // Actualizar campos base
        Database::query(
            "UPDATE usuarios SET nombre = ?, email = ?, rol = ?, activo = ? WHERE id = ?",
            [$data['nombre'], $data['email'], $data['rol'], (int)!empty($data['activo']), $id]
        );

        // Cambiar contraseña solo si se proporcionó
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                return ['ok' => false, 'msg' => 'La contraseña debe tener al menos 8 caracteres.'];
            }
            $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            Database::query('UPDATE usuarios SET password_hash = ? WHERE id = ?', [$hash, $id]);
        }

        Logger::write('usuario.editar', 'usuarios', $id);
        return ['ok' => true, 'msg' => 'Usuario actualizado.'];
    }

    public static function eliminar(int $id): array
    {
        if ($id === Auth::id()) {
            return ['ok' => false, 'msg' => 'No puedes eliminarte a ti mismo.'];
        }
        // Verificar que no sea el único superadmin
        $user = self::findById($id);
        if ($user && $user['rol'] === 'superadmin') {
            $total = (int) Database::scalar(
                "SELECT COUNT(*) FROM usuarios WHERE rol = 'superadmin' AND activo = 1"
            );
            if ($total <= 1) {
                return ['ok' => false, 'msg' => 'No puedes eliminar el único superadministrador.'];
            }
        }
        Database::query('DELETE FROM usuarios WHERE id = ?', [$id]);
        Logger::write('usuario.eliminar', 'usuarios', $id);
        return ['ok' => true, 'msg' => 'Usuario eliminado.'];
    }
}