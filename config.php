<?php
// ============================================================
// Configuración global
// ============================================================

// Institución
define('PROYECT_NAME',       'Documentos');
define('PROYECT_NAME_SUB', 'Documentos');
define('EMAIL',        'info@info.com');
define('GITHUB_URL',        'https://github.com/overlordantony');
define('GITHUB_NAME',       'OverlordAntony');

// Base de datos
define('DB_HOST',    'localhost');
define('DB_NAME',    'repositorio_docs');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// Rutas
define('ROOT_PATH',    __DIR__);
define('SRC_PATH',     ROOT_PATH . '/src');
define('TPL_PATH',     ROOT_PATH . '/templates');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('PUBLIC_PATH',  ROOT_PATH . '/public');

// URL pública de uploads (relativa al dominio)
define('UPLOADS_URL', '/uploads/');

// Logo (relativo a public/)
define('LOGO_FILE', 'assets/img/logo.png');

// Subida de archivos
define('MAX_UPLOAD_MB',    50);
define('ALLOWED_EXT', ['pdf','doc','docx','ppt','pptx','xls','xlsx', 'png','jpg','jpeg']);

// Paginación
define('PER_PAGE_PUBLIC', 15);
define('PER_PAGE_ADMIN',  20);

// Entorno: 'development' | 'production'
define('APP_ENV', 'development');

// Mostrar errores solo en desarrollo
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Zona horaria
date_default_timezone_set('America/Bogota');

// BASE URL dinámica
if (!defined('BASE_URL')) {
    // Tomamos el SCRIPT_NAME del archivo que inició la petición
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    // Normalizar separadores y eliminamos segmentos internos de admin
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $scriptDir = preg_replace('#/admin$#', '', $scriptDir);

    define('BASE_URL', $scriptDir); // ej: /2026/repositorio/public
}
