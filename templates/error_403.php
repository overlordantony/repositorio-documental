<?php
// templates/error_403.php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acceso denegado</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@600&family=DM+Sans:wght@400&display=swap" rel="stylesheet">
<style>
  body{font-family:'DM Sans',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f2ed;margin:0}
  .box{text-align:center;padding:40px}
  h1{font-family:'Lora',serif;font-size:48px;color:#1a3a6e;margin-bottom:8px}
  p{color:#5a5a5a;margin-bottom:24px}
  a{color:#1a3a6e;font-weight:500}
</style>
</head>
<body>
  <div class="box">
    <h1>403</h1>
    <p>No tienes permiso para acceder a esta sección.</p>
    <a href="<?= BASE_URL ?>/admin/panel.php">← Volver al panel</a>
  </div>
</body>
</html>
