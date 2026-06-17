<?php
// public/admin/log.php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::require();
Auth::requirePermiso('log.ver');

$logs      = Logger::recientes(100);
$pageTitle = 'Actividad del sistema';
$navActivo = 'log';
include_once TPL_PATH . '/layout_admin_top.php';
?>

<div class="page-title">Actividad del sistema <span>(últimas 100 acciones)</span></div>

<div class="card">
  <table class="tabla">
    <thead>
      <tr><th>Acción</th><th>Detalle</th><th>Usuario</th><th>IP</th><th>Fecha</th></tr>
    </thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
      <tr>
        <td><code style="font-size:12px;color:var(--azul)"><?= e($l['accion']) ?></code></td>
        <td style="font-size:12px;color:var(--texto-2)"><?= e(mb_strimwidth($l['detalle'] ?? '', 0, 70, '…')) ?></td>
        <td style="font-size:12px"><?= e($l['usuario_nombre'] ?? 'Sistema') ?></td>
        <td style="font-size:11px;color:var(--texto-3)"><?= e($l['ip'] ?? '') ?></td>
        <td style="font-size:11px;white-space:nowrap;color:var(--texto-2)">
          <?= date('d/m/Y H:i:s', strtotime($l['creado_en'])) ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include_once TPL_PATH . '/layout_admin_bottom.php'; ?>
