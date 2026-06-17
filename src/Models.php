<?php
// ============================================================
// src/Models.php — User, Category, Dependency
// ============================================================

// ============================================================
// User
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
        if (empty($data['nombre']) || empty($data['usuario']) || empty($data['email']) || empty($data['password'])) {
            return ['ok' => false, 'msg' => 'Todos los campos son obligatorios.'];
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Email no válido.'];
        }
        if (strlen($data['password']) < 8) {
            return ['ok' => false, 'msg' => 'La contraseña debe tener al menos 8 caracteres.'];
        }
        if (!in_array($data['rol'], ['superadmin', 'editor', 'viewer'], true)) {
            return ['ok' => false, 'msg' => 'Rol no válido.'];
        }

        $existe = Database::scalar(
            'SELECT COUNT(*) FROM usuarios WHERE usuario = ? OR email = ?',
            [$data['usuario'], $data['email']]
        );
        if ($existe > 0) {
            return ['ok' => false, 'msg' => 'El nombre de usuario o el email ya están en uso.'];
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
        // Validaciones básicas
        if (empty($data['nombre'])) {
            return ['ok' => false, 'msg' => 'El nombre es obligatorio.'];
        }
        if (empty($data['usuario'])) {
            return ['ok' => false, 'msg' => 'El nombre de usuario es obligatorio.'];
        }
        if (empty($data['email'])) {
            return ['ok' => false, 'msg' => 'El email es obligatorio.'];
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Email no válido.'];
        }
        if (!in_array($data['rol'], ['superadmin', 'editor', 'viewer'], true)) {
            return ['ok' => false, 'msg' => 'Rol no válido.'];
        }

        // Solo superadmin puede cambiar roles
        if (!Auth::esSuperadmin() && isset($data['rol'])) {
            unset($data['rol']);
        }

        // Verificar que usuario y email no estén en uso por otro
        $duplicado = Database::scalar(
            'SELECT COUNT(*) FROM usuarios WHERE (usuario = ? OR email = ?) AND id != ?',
            [$data['usuario'], $data['email'], $id]
        );
        if ($duplicado > 0) {
            return ['ok' => false, 'msg' => 'El nombre de usuario o el email ya está en uso por otro usuario.'];
        }

        // Proteger: no degradar el último superadmin
        $user = self::findById($id);
        if ($user && $user['rol'] === 'superadmin' && ($data['rol'] ?? 'superadmin') !== 'superadmin') {
            $totalSuper = (int) Database::scalar(
                "SELECT COUNT(*) FROM usuarios WHERE rol = 'superadmin' AND activo = 1"
            );
            if ($totalSuper <= 1) {
                return ['ok' => false, 'msg' => 'No puedes cambiar el rol del único superadministrador.'];
            }
        }

        Database::query(
            "UPDATE usuarios
             SET nombre = ?, usuario = ?, email = ?, rol = ?, activo = ?
             WHERE id = ?",
            [
                $data['nombre'],
                $data['usuario'],
                $data['email'],
                $data['rol'],
                (int)!empty($data['activo']),
                $id,
            ]
        );

        // Actualizar sesión si se editó el propio usuario
        if ($id === Auth::id()) {
            $_SESSION['auth']['nombre']  = $data['nombre'];
            $_SESSION['auth']['usuario'] = $data['usuario'];
            $_SESSION['auth']['email']   = $data['email'];
            $_SESSION['auth']['rol']     = $data['rol'];
        }

        Logger::write('usuario.editar', 'usuarios', $id, $data['usuario']);
        return ['ok' => true, 'msg' => 'Usuario actualizado correctamente.'];
    }

    public static function cambiarPassword(int $id, string $nueva, ?string $actual = null): array
    {
        if (strlen($nueva) < 8) {
            return ['ok' => false, 'msg' => 'La contraseña debe tener al menos 8 caracteres.'];
        }

        // Si es el propio usuario, verificar contraseña actual
        if ($id === Auth::id()) {
            if (empty($actual)) {
                return ['ok' => false, 'msg' => 'Debes ingresar tu contraseña actual.'];
            }
            $user = self::findById($id);
            if (!$user || !password_verify($actual, $user['password_hash'])) {
                return ['ok' => false, 'msg' => 'La contraseña actual es incorrecta.'];
            }
        }

        $hash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::query('UPDATE usuarios SET password_hash = ? WHERE id = ?', [$hash, $id]);
        Logger::write('usuario.cambio_password', 'usuarios', $id);
        return ['ok' => true, 'msg' => 'Contraseña actualizada correctamente.'];
    }

    public static function eliminar(int $id): array
    {
        if ($id === Auth::id()) {
            return ['ok' => false, 'msg' => 'No puedes eliminarte a ti mismo.'];
        }
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


// ============================================================
// Category
// ============================================================
class Category
{
    public static function all(bool $soloActivas = false): array
    {
        $where = $soloActivas ? 'WHERE activo = 1' : '';
        return Database::all("SELECT * FROM categorias $where ORDER BY orden, nombre");
    }

    public static function findById(int $id): ?array
    {
        return Database::row('SELECT * FROM categorias WHERE id = ?', [$id]);
    }

    public static function crear(array $data): array
    {
        if (empty($data['nombre'])) return ['ok' => false, 'msg' => 'El nombre es obligatorio.'];
        $existe = Database::scalar('SELECT COUNT(*) FROM categorias WHERE nombre = ?', [$data['nombre']]);
        if ($existe) return ['ok' => false, 'msg' => 'Ya existe una categoría con ese nombre.'];

        Database::query(
            "INSERT INTO categorias (nombre, color_texto, color_fondo, orden) VALUES (?, ?, ?, ?)",
            [$data['nombre'], $data['color_texto'] ?? '#185FA5', $data['color_fondo'] ?? '#E6F1FB', (int)($data['orden'] ?? 0)]
        );
        Logger::write('categoria.crear', 'categorias', (int)Database::lastId(), $data['nombre']);
        return ['ok' => true, 'msg' => 'Categoría creada.'];
    }

    public static function editar(int $id, array $data): array
    {
        if (empty($data['nombre'])) return ['ok' => false, 'msg' => 'El nombre es obligatorio.'];
        $existe = Database::scalar(
            'SELECT COUNT(*) FROM categorias WHERE nombre = ? AND id != ?',
            [$data['nombre'], $id]
        );
        if ($existe) return ['ok' => false, 'msg' => 'Ya existe otra categoría con ese nombre.'];

        Database::query(
            "UPDATE categorias SET nombre = ?, color_texto = ?, color_fondo = ?, orden = ?, activo = ? WHERE id = ?",
            [$data['nombre'], $data['color_texto'], $data['color_fondo'], (int)$data['orden'], (int)!empty($data['activo']), $id]
        );
        Logger::write('categoria.editar', 'categorias', $id, $data['nombre']);
        return ['ok' => true, 'msg' => 'Categoría actualizada.'];
    }

    public static function eliminar(int $id): array
    {
        $enUso = (int) Database::scalar('SELECT COUNT(*) FROM documentos WHERE categoria_id = ?', [$id]);
        if ($enUso > 0) return ['ok' => false, 'msg' => "No se puede eliminar: hay $enUso documentos con esta categoría."];
        Database::query('DELETE FROM categorias WHERE id = ?', [$id]);
        Logger::write('categoria.eliminar', 'categorias', $id);
        return ['ok' => true, 'msg' => 'Categoría eliminada.'];
    }

    public static function conConteo(): array
    {
        return Database::all(
            "SELECT c.*, COUNT(d.id) AS total_docs
             FROM categorias c
             LEFT JOIN documentos d ON d.categoria_id = c.id AND d.activo = 1
             WHERE c.activo = 1
             GROUP BY c.id
             ORDER BY c.orden, c.nombre"
        );
    }
}


// ============================================================
// Dependency
// ============================================================
class Dependency
{
    public static function all(bool $soloActivas = false): array
    {
        $where = $soloActivas ? 'WHERE activo = 1' : '';
        return Database::all("SELECT * FROM dependencias $where ORDER BY nombre");
    }

    public static function findById(int $id): ?array
    {
        return Database::row('SELECT * FROM dependencias WHERE id = ?', [$id]);
    }

    public static function crear(array $data): array
    {
        if (empty($data['nombre'])) return ['ok' => false, 'msg' => 'El nombre es obligatorio.'];
        $existe = Database::scalar('SELECT COUNT(*) FROM dependencias WHERE nombre = ?', [$data['nombre']]);
        if ($existe) return ['ok' => false, 'msg' => 'Ya existe una dependencia con ese nombre.'];

        Database::query(
            "INSERT INTO dependencias (nombre, sigla) VALUES (?, ?)",
            [$data['nombre'], $data['sigla'] ?? null]
        );
        Logger::write('dependencia.crear', 'dependencias', (int)Database::lastId(), $data['nombre']);
        return ['ok' => true, 'msg' => 'Dependencia creada.'];
    }

    public static function editar(int $id, array $data): array
    {
        if (empty($data['nombre'])) return ['ok' => false, 'msg' => 'El nombre es obligatorio.'];
        $existe = Database::scalar(
            'SELECT COUNT(*) FROM dependencias WHERE nombre = ? AND id != ?',
            [$data['nombre'], $id]
        );
        if ($existe) return ['ok' => false, 'msg' => 'Ya existe otra dependencia con ese nombre.'];

        Database::query(
            "UPDATE dependencias SET nombre = ?, sigla = ?, activo = ? WHERE id = ?",
            [$data['nombre'], $data['sigla'] ?? null, (int)!empty($data['activo']), $id]
        );
        Logger::write('dependencia.editar', 'dependencias', $id, $data['nombre']);
        return ['ok' => true, 'msg' => 'Dependencia actualizada.'];
    }

    public static function eliminar(int $id): array
    {
        $enUso = (int) Database::scalar('SELECT COUNT(*) FROM documentos WHERE dependencia_id = ?', [$id]);
        if ($enUso > 0) return ['ok' => false, 'msg' => "No se puede eliminar: hay $enUso documentos de esta dependencia."];
        Database::query('DELETE FROM dependencias WHERE id = ?', [$id]);
        Logger::write('dependencia.eliminar', 'dependencias', $id);
        return ['ok' => true, 'msg' => 'Dependencia eliminada.'];
    }
}
