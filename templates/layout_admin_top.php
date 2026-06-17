<?php
// templates/layout_admin_top.php
// Variables esperadas: $pageTitle, $navActivo
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Panel') ?> — Admin <?= e(INST_NOMBRE_CORTO) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>

<div class="topbar">
  <a href="<?= BASE_URL ?>/admin/panel.php" class="topbar-logo">
    <?php if (file_exists(PUBLIC_PATH . '/' . LOGO_FILE)): ?>
      <img src="<?= BASE_URL ?>/<?= e(LOGO_FILE) ?>" alt="Logo">
    <?php else: ?>
      <div class="lph"><?= e(substr(INST_NOMBRE_CORTO, 0, 1)) ?></div>
    <?php endif; ?>
    <div>
      <h1><?= e(INST_NOMBRE_CORTO) ?> · Panel Admin</h1>
      <p><?= e(Auth::nombre()) ?> — <?= e(labelRol(Auth::rol())) ?></p>
    </div>
  </a>
  <div class="topbar-actions">
    <a href="<?= BASE_URL ?>/" target="_blank" class="tb-btn">Ver sitio público</a>
    <a href="<?= BASE_URL ?>/admin/logout.php"
       class="tb-btn danger"
       data-confirm="¿Cerrar sesión?">Cerrar sesión</a>
  </div>
</div>

<div class="admin-wrap">
  <nav class="admin-nav">
    <div class="nav-section">
      <div class="nav-label">Principal</div>
      <a href="<?= BASE_URL ?>/admin/panel.php"
         class="nav-item <?= ($navActivo??'') === 'panel' ? 'activo' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
          <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
        </svg>
        Dashboard
      </a>
      <a href="<?= BASE_URL ?>/admin/documentos.php"
         class="nav-item <?= ($navActivo??'') === 'documentos' ? 'activo' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
        </svg>
        Documentos
      </a>
    </div>

    <div class="nav-section">
      <div class="nav-label">Configuración</div>
      <a href="<?= BASE_URL ?>/admin/categorias.php"
         class="nav-item <?= ($navActivo??'') === 'categorias' ? 'activo' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
          <line x1="7" y1="7" x2="7.01" y2="7"/>
        </svg>
        Categorías
      </a>
      <a href="<?= BASE_URL ?>/admin/dependencias.php"
         class="nav-item <?= ($navActivo??'') === 'dependencias' ? 'activo' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        Dependencias
      </a>
      <?php if (Auth::can('usuarios.ver')): ?>
      <a href="<?= BASE_URL ?>/admin/usuarios.php"
         class="nav-item <?= ($navActivo??'') === 'usuarios' ? 'activo' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
        </svg>
        Usuarios
      </a>
      <?php endif; ?>
      <?php if (Auth::can('log.ver')): ?>
      <a href="<?= BASE_URL ?>/admin/log.php"
         class="nav-item <?= ($navActivo??'') === 'log' ? 'activo' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        Actividad
      </a>
      <?php endif; ?>
    </div>
  </nav>

  <main class="admin-content">
    <?php
    $flash = flashGet();
    if ($flash):
      $cls = $flash['tipo'] === 'ok' ? 'alert-ok' : 'alert-error';
    ?>
      <div class="alert <?= $cls ?>" data-auto-close><?= e($flash['msg']) ?></div>
    <?php endif; ?>
