<?php

// ============================================================
// src/Category.php — Modelo de categorías
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