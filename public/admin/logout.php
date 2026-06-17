<?php
// public/admin/logout.php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::require();
// El logout no necesita CSRF — basta con verificar sesión activa.
// Un atacante no puede forzar logout sin que sea un problema real de seguridad.
Auth::logout();
redirect('/admin/');
