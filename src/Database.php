<?php
// ============================================================
// src/Database.php — Conexión PDO Singleton
// ============================================================

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_FOUND_ROWS   => true,
                ]);
            } catch (PDOException $e) {
                // En producción no exponer detalles
                $msg = APP_ENV === 'development'
                    ? $e->getMessage()
                    : 'Error de conexión a la base de datos.';
                http_response_code(500);
                die(self::errorHtml($msg));
            }
        }
        return self::$instance;
    }

    /** Shortcut para preparar y ejecutar */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Obtener una sola fila */
    public static function row(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    /** Obtener todas las filas */
    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** Obtener un escalar */
    public static function scalar(string $sql, array $params = [])
    {
        return self::query($sql, $params)->fetchColumn();
    }

    /** Último ID insertado */
    public static function lastId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    private static function errorHtml(string $msg): string
    {
        return '<div style="font-family:sans-serif;padding:2rem;color:#a00">
            <strong>Error de base de datos</strong><br>' . htmlspecialchars($msg) . '
        </div>';
    }
}
