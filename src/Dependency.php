<?php

// ============================================================
// src/Dependency.php — Modelo de dependencias
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
