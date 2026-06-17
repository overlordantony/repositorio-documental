<?php
// ============================================================
// src/Document.php — Modelo de documentos
// ============================================================

class Document
{
    /** Listar documentos públicos con filtros y paginación */
    public static function listarPublico(array $filtros, int $limit, int $offset): array
    {
        [$where, $params] = self::buildWhere($filtros, publico: true);

        $sql = "SELECT d.*, c.nombre AS categoria_nombre, c.color_texto, c.color_fondo,
                       dep.nombre AS dependencia_nombre
                FROM documentos d
                JOIN categorias   c   ON d.categoria_id   = c.id
                JOIN dependencias dep ON d.dependencia_id  = dep.id
                $where
                ORDER BY d.fecha_publicacion DESC
                LIMIT ? OFFSET ?";

        return Database::all($sql, [...$params, $limit, $offset]);
    }

    public static function contarPublico(array $filtros): int
    {
        [$where, $params] = self::buildWhere($filtros, publico: true);
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM documentos d $where", $params
        );
    }

    /** Listar para panel admin */
    public static function listarAdmin(array $filtros, int $limit, int $offset): array
    {
        [$where, $params] = self::buildWhere($filtros, publico: false);

        $sql = "SELECT d.*, c.nombre AS categoria_nombre,
                       dep.nombre AS dependencia_nombre,
                       u.nombre AS subido_por_nombre
                FROM documentos d
                JOIN categorias   c   ON d.categoria_id   = c.id
                JOIN dependencias dep ON d.dependencia_id  = dep.id
                LEFT JOIN usuarios u  ON d.subido_por      = u.id
                $where
                ORDER BY d.creado_en DESC
                LIMIT ? OFFSET ?";

        return Database::all($sql, [...$params, $limit, $offset]);
    }

    public static function contarAdmin(array $filtros): int
    {
        [$where, $params] = self::buildWhere($filtros, publico: false);
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM documentos d $where", $params
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::row(
            "SELECT d.*, c.nombre AS categoria_nombre, dep.nombre AS dependencia_nombre
             FROM documentos d
             JOIN categorias   c   ON d.categoria_id  = c.id
             JOIN dependencias dep ON d.dependencia_id = dep.id
             WHERE d.id = ?",
            [$id]
        );
    }

    /** Subir nuevo documento */
    public static function crear(array $data, array $file): array
    {
        $validacion = self::validarArchivo($file);
        if (!$validacion['ok']) return $validacion;

        $ext           = $validacion['ext'];
        $nombreArchivo = sanitizarNombreArchivo($data['titulo'], $ext);
        $destino       = UPLOADS_PATH . '/' . $nombreArchivo;

        if (!is_dir(UPLOADS_PATH)) mkdir(UPLOADS_PATH, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            return ['ok' => false, 'msg' => 'No se pudo guardar el archivo. Verifica permisos de uploads/.'];
        }

        $tipo = normalizarTipo($ext);
        $kb   = (int) ceil($file['size'] / 1024);
        $anio = date('Y', strtotime($data['fecha_publicacion']));

        Database::query(
            "INSERT INTO documentos
             (titulo, descripcion, categoria_id, dependencia_id, archivo, tipo_archivo, tamanio_kb, anio, fecha_publicacion, subido_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['titulo'], $data['descripcion'],
                $data['categoria_id'], $data['dependencia_id'],
                $nombreArchivo, $tipo, $kb, $anio,
                $data['fecha_publicacion'], Auth::id(),
            ]
        );

        $newId = (int) Database::lastId();
        Logger::write('documento.crear', 'documentos', $newId, $data['titulo']);
        return ['ok' => true, 'msg' => 'Documento publicado correctamente.', 'id' => $newId];
    }

    /** Editar documento — el archivo es opcional */
    public static function editar(int $id, array $data, ?array $file = null): array
    {
        $doc = self::findById($id);
        if (!$doc) return ['ok' => false, 'msg' => 'Documento no encontrado.'];

        $nombreArchivo = $doc['archivo'];
        $tipo          = $doc['tipo_archivo'];
        $kb            = $doc['tamanio_kb'];

        // Si se subió un archivo nuevo, reemplazar el anterior
        if ($file && !empty($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK) {
            $validacion = self::validarArchivo($file);
            if (!$validacion['ok']) return $validacion;

            $ext           = $validacion['ext'];
            $nuevoNombre   = sanitizarNombreArchivo($data['titulo'], $ext);
            $destino       = UPLOADS_PATH . '/' . $nuevoNombre;

            if (!is_dir(UPLOADS_PATH)) mkdir(UPLOADS_PATH, 0755, true);

            if (!move_uploaded_file($file['tmp_name'], $destino)) {
                return ['ok' => false, 'msg' => 'No se pudo guardar el nuevo archivo.'];
            }

            // Eliminar archivo anterior
            $rutaAnterior = UPLOADS_PATH . '/' . $doc['archivo'];
            if (file_exists($rutaAnterior)) unlink($rutaAnterior);

            $nombreArchivo = $nuevoNombre;
            $tipo          = normalizarTipo($ext);
            $kb            = (int) ceil($file['size'] / 1024);
        }

        $anio = date('Y', strtotime($data['fecha_publicacion']));

        Database::query(
            "UPDATE documentos SET
               titulo = ?, descripcion = ?, categoria_id = ?, dependencia_id = ?,
               archivo = ?, tipo_archivo = ?, tamanio_kb = ?,
               anio = ?, fecha_publicacion = ?, activo = ?
             WHERE id = ?",
            [
                $data['titulo'], $data['descripcion'],
                $data['categoria_id'], $data['dependencia_id'],
                $nombreArchivo, $tipo, $kb,
                $anio, $data['fecha_publicacion'],
                (int)!empty($data['activo']),
                $id,
            ]
        );

        Logger::write('documento.editar', 'documentos', $id, $data['titulo']);
        return ['ok' => true, 'msg' => 'Documento actualizado correctamente.'];
    }

    /** Eliminar documento y su archivo físico */
    public static function eliminar(int $id): array
    {
        $doc = self::findById($id);
        if (!$doc) return ['ok' => false, 'msg' => 'Documento no encontrado.'];

        $ruta = UPLOADS_PATH . '/' . $doc['archivo'];
        if (file_exists($ruta)) unlink($ruta);

        Database::query('DELETE FROM documentos WHERE id = ?', [$id]);
        Logger::write('documento.eliminar', 'documentos', $id, $doc['titulo']);
        return ['ok' => true, 'msg' => 'Documento eliminado.'];
    }

    /** Años disponibles con documentos */
    public static function aniosDisponibles(): array
    {
        return Database::all(
            'SELECT DISTINCT anio FROM documentos WHERE activo = 1 ORDER BY anio DESC'
        );
    }

    /** Estadísticas rápidas */
    public static function stats(): array
    {
        return Database::row(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(tamanio_kb), 0) AS peso_total,
                    COUNT(CASE WHEN creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) AS este_mes
             FROM documentos WHERE activo = 1"
        ) ?? ['total' => 0, 'peso_total' => 0, 'este_mes' => 0];
    }

    // ── Helpers privados ───────────────────────────────────

    private static function validarArchivo(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'msg' => 'Error al recibir el archivo (código ' . $file['error'] . ').'];
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXT, true)) {
            return ['ok' => false, 'msg' => 'Tipo de archivo no permitido. Solo: ' . implode(', ', ALLOWED_EXT)];
        }
        if ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
            return ['ok' => false, 'msg' => 'El archivo supera el límite de ' . MAX_UPLOAD_MB . ' MB.'];
        }
        return ['ok' => true, 'ext' => $ext];
    }

    private static function buildWhere(array $f, bool $publico): array
    {
        $conds  = $publico ? ['d.activo = 1'] : [];
        $params = [];

        if (!empty($f['q'])) {
            $conds[]  = '(d.titulo LIKE ? OR d.descripcion LIKE ?)';
            $params[] = '%' . $f['q'] . '%';
            $params[] = '%' . $f['q'] . '%';
        }
        if (!empty($f['categoria_id'])) {
            $conds[]  = 'd.categoria_id = ?';
            $params[] = (int) $f['categoria_id'];
        }
        if (!empty($f['dependencia_id'])) {
            $conds[]  = 'd.dependencia_id = ?';
            $params[] = (int) $f['dependencia_id'];
        }
        if (!empty($f['anio'])) {
            $conds[]  = 'd.anio = ?';
            $params[] = (int) $f['anio'];
        }

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        return [$where, $params];
    }
}
