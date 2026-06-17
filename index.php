<?php
// repo_v2/index.php
// Redirige automáticamente al repositorio público
// Funciona sin importar en qué subdirectorio esté el proyecto

$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
header('Location: ' . $base . '/public/');
exit;
