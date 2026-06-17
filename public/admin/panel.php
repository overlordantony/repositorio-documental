<?php
// public/admin/panel.php — Dashboard
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::require();
Auth::requirePermiso('documentos.ver');

$stats     = Document::stats();
$recientes = Logger::recientes(10);
$pageTitle = 'Dashboard';
$navActivo = 'panel';
include_once TPL_PATH . '/layout_admin_top.php';
?>

<div class="page-title">Dashboard</div>

<div class="grid-3">
  <div class="card stat-card">
    <div class="stat-num"><?= number_format($stats['total']) ?></div>
    <div class="stat-lbl">Documentos publicados</div>
  </div>
  <div class="card stat-card">
    <div class="stat-num"><?= formatSize((int)$stats['peso_total']) ?></div>
    <div class="stat-lbl">Espacio total usado</div>
  </div>
  <div class="card stat-card">
    <div class="stat-num"><?= $stats['este_mes'] ?></div>
    <div class="stat-lbl">Subidos este mes</div>
  </div>
</div>

<div class="card">
  <div class="card-title">
    Actividad reciente
    <?php if (Auth::can('log.ver')): ?>
      <a href="<?= BASE_URL ?>/admin/log.php" class="btn btn-sm btn-outline">Ver todo</a>
    <?php endif; ?>
  </div>
  <?php if (empty($recientes)): ?>
    <p style="color:var(--texto-2);text-align:center;padding:20px">Sin actividad registrada.</p>
  <?php else: ?>
  <table class="tabla">
    <thead>
      <tr><th>Acción</th><th>Usuario</th><th>Fecha</th><th>IP</th></tr>
    </thead>
    <tbody>
    <?php foreach ($recientes as $log): ?>
      <tr>
        <td>
          <code style="font-size:12px;color:var(--azul)"><?= e($log['accion']) ?></code>
          <?php if ($log['detalle']): ?>
            <br><small style="color:var(--texto-2)"><?= e(mb_strimwidth($log['detalle'], 0, 60, '…')) ?></small>
          <?php endif; ?>
        </td>
        <td style="font-size:12px"><?= e($log['usuario_nombre'] ?? '—') ?></td>
        <td style="font-size:12px;white-space:nowrap"><?= date('d/m/Y H:i', strtotime($log['creado_en'])) ?></td>
        <td style="font-size:11px;color:var(--texto-3)"><?= e($log['ip'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php include_once TPL_PATH . '/layout_admin_bottom.php'; ?>
