<?php
// public/admin/index.php  (antes login.php)
// Abrir /admin/ ya abre el login directamente
require_once __DIR__ . '/../../src/bootstrap.php';

if (Auth::check()) redirect('/admin/panel.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $error = 'Completa todos los campos.';
    } elseif (Auth::login($usuario, $password)) {
        redirect('/admin/panel.php');
    } else {
        $error = 'Usuario o contraseña incorrectos.';
        sleep(1);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Acceso — <?= e(INST_NOMBRE) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<style>
  body { display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:100vh; padding:24px; background:var(--bg); }
  .login-card { background:#fff; border:1px solid var(--borde); border-radius:12px; padding:40px; width:100%; max-width:380px; box-shadow:0 4px 24px rgba(26,58,110,.08); }
  .logo-area { text-align:center; margin-bottom:24px; }
  .logo-area img { height:60px; margin-bottom:12px; display:block; margin-inline:auto; }
  .logo-area h1 { font-family:'Lora',serif; font-size:17px; color:var(--azul); font-weight:600; }
  .logo-area p  { font-size:12px; color:var(--texto-3); margin-top:4px; }
  .sep { height:1px; background:var(--borde); margin:18px 0; }
  .back { display:block; text-align:center; margin-top:18px; font-size:12px; color:var(--texto-3); text-decoration:none; }
  .back:hover { color:var(--azul); }
</style>
</head>
<body>
<div class="login-card">
  <div class="logo-area">
    <?php if (file_exists(PUBLIC_PATH . '/' . LOGO_FILE)): ?>
      <img src="<?= BASE_URL ?>/<?= e(LOGO_FILE) ?>" alt="Logo">
    <?php endif; ?>
    <h1><?= e(INST_NOMBRE) ?></h1>
    <p>Panel de administración</p>
  </div>
  <div class="sep"></div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <?= csrfField() ?>
    <div class="form-group">
      <label for="usuario">Usuario</label>
      <input type="text" id="usuario" name="usuario"
             value="<?= e($_POST['usuario'] ?? '') ?>"
             autocomplete="username" required autofocus>
    </div>
    <div class="form-group">
      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password"
             autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-full" style="margin-top:4px">
      Ingresar
    </button>
  </form>
</div>
<a href="<?= BASE_URL ?>/" class="back">← Volver al repositorio</a>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
