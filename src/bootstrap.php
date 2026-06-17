<?php
// ============================================================
// src/bootstrap.php — Carga inicial del sistema
// Incluir este archivo al inicio de cada página PHP
// ============================================================

require_once __DIR__ . '/../config.php';

// Autoloader simple para clases en src/
spl_autoload_register(function (string $class): void {
    $file = SRC_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Helpers globales
require_once SRC_PATH . '/Helpers.php';

// Iniciar sesión segura
Auth::startSession();

// Cargar modelos
require_once SRC_PATH . '/Models.php';

// Cabeceras de seguridad HTTP
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'; img-src 'self' data:");
