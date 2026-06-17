<?php
// templates/layout_public_top.php
// Variables esperadas: $pageTitle (string), $filtros (array)
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Repositorio') ?> — <?= e(INST_NOMBRE) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/public.css">
</head>
<body>

<header class="header">
  <a href="<?= BASE_URL ?>/" class="header-logo">
    <?php if (file_exists(PUBLIC_PATH . '/' . LOGO_FILE)): ?>
      <img src="<?= BASE_URL ?>/<?= e(LOGO_FILE) ?>" alt="Logo <?= e(INST_NOMBRE) ?>">
    <?php else: ?>
      <div class="logo-placeholder"><?= e(substr(INST_NOMBRE_CORTO, 0, 1)) ?></div>
    <?php endif; ?>
    <div class="header-brand">
      <h1><?= e(INST_NOMBRE) ?></h1>
      <p>Repositorio Documental</p>
    </div>
  </a>
  <div class="header-search">
    <form method="get" action="<?= BASE_URL ?>/">
      <?php if (!empty($filtros['categoria_id'])): ?>
        <input type="hidden" name="categoria_id" value="<?= e($filtros['categoria_id']) ?>">
      <?php endif; ?>
      <?php if (!empty($filtros['anio'])): ?>
        <input type="hidden" name="anio" value="<?= e($filtros['anio']) ?>">
      <?php endif; ?>
      <input type="text" name="q" value="<?= e($filtros['q'] ?? '') ?>"
             placeholder="Buscar resoluciones, acuerdos, circulares...">
      <button type="submit">Buscar</button>
    </form>
  </div>
</header>
