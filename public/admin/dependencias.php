<?php
// public/admin/dependencias.php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::require();
Auth::requirePermiso('dependencias.ver');

// ── Eliminar ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    Auth::requirePermiso('dependencias.eliminar');
    csrfCheck();
    $r = Dependency::eliminar((int)($_POST['id'] ?? 0));
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/dependencias.php');
}

// ── Crear ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    Auth::requirePermiso('dependencias.crear');
    csrfCheck();
    $r = Dependency::crear([
        'nombre' => trim($_POST['nombre'] ?? ''),
        'sigla'  => strtoupper(trim($_POST['sigla'] ?? '')),
    ]);
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/dependencias.php');
}

// ── Editar ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar') {
    Auth::requirePermiso('dependencias.editar');
    csrfCheck();
    $r = Dependency::editar((int)($_POST['id'] ?? 0), [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'sigla'  => strtoupper(trim($_POST['sigla'] ?? '')),
        'activo' => (int)!empty($_POST['activo']),
    ]);
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/dependencias.php');
}

$dependencias = Dependency::all();
$pageTitle    = 'Dependencias';
$navActivo    = 'dependencias';
include_once TPL_PATH . '/layout_admin_top.php';
?>

<div class="page-title">Dependencias</div>

<div class="grid-2">

  <!-- ── CREAR ── -->
  <?php if (Auth::can('dependencias.crear')): ?>
  <div class="card">
    <div class="card-title">Nueva dependencia</div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="accion" value="crear">
      <div class="form-group">
        <label>Nombre completo *</label>
        <input type="text" name="nombre" required
               placeholder="Ej: Vicerrectoría de Investigación">
      </div>
      <div class="form-group">
        <label>Sigla (opcional)</label>
        <input type="text" name="sigla" placeholder="Ej: VRI"
               style="max-width:140px" maxlength="20">
      </div>
      <button type="submit" class="btn btn-primary btn-full">Crear dependencia</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- ── LISTADO ── -->
  <div class="card">
    <div class="card-title">Dependencias (<?= count($dependencias) ?>)</div>
    <table class="tabla">
      <thead>
        <tr><th>Nombre</th><th>Sigla</th><th>Estado</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($dependencias as $d): ?>
        <tr>
          <td style="font-size:13px;font-weight:500"><?= e($d['nombre']) ?></td>
          <td style="font-size:12px;color:var(--texto-2)"><?= e($d['sigla'] ?? '—') ?></td>
          <td>
            <span class="badge" style="<?= $d['activo']
              ? 'background:#eaf3de;color:#3b6d11'
              : 'background:#f1efe8;color:#999' ?>">
              <?= $d['activo'] ? 'Activa' : 'Inactiva' ?>
            </span>
          </td>
          <td class="acciones">
            <?php if (Auth::can('dependencias.editar')): ?>
              <button class="btn btn-sm btn-outline"
                      onclick="editarDependencia(<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>)">
                Editar
              </button>
            <?php endif; ?>
            <?php if (Auth::can('dependencias.eliminar')): ?>
              <form method="post" style="display:inline">
                <?= csrfField() ?>
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm="¿Eliminar «<?= e($d['nombre']) ?>»?">
                  Eliminar
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── MODAL EDITAR ── -->
<div id="modal-editar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:32px;width:100%;max-width:420px;margin:16px;box-shadow:0 8px 40px rgba(0,0,0,.2)">
    <h3 style="font-family:'Lora',serif;font-size:16px;color:var(--azul);margin-bottom:20px">
      Editar dependencia
    </h3>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="accion" value="editar">
      <input type="hidden" name="id" id="edit-id">

      <div class="form-group">
        <label>Nombre completo *</label>
        <input type="text" name="nombre" id="edit-nombre" required>
      </div>
      <div class="form-group">
        <label>Sigla</label>
        <input type="text" name="sigla" id="edit-sigla"
               style="max-width:140px" maxlength="20">
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" name="activo" id="edit-activo" value="1">
          Dependencia activa
        </label>
      </div>

      <div style="display:flex;gap:10px;margin-top:12px">
        <button type="submit" class="btn btn-primary" style="flex:1">Guardar cambios</button>
        <button type="button" class="btn btn-outline" onclick="cerrarModal()" style="flex:1">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
function editarDependencia(d) {
  document.getElementById('edit-id').value     = d.id;
  document.getElementById('edit-nombre').value = d.nombre;
  document.getElementById('edit-sigla').value  = d.sigla ?? '';
  document.getElementById('edit-activo').checked = d.activo == 1;
  document.getElementById('modal-editar').style.display = 'flex';
}
function cerrarModal() {
  document.getElementById('modal-editar').style.display = 'none';
}
document.getElementById('modal-editar').addEventListener('click', function(e) {
  if (e.target === this) cerrarModal();
});
</script>

<?php include_once TPL_PATH . '/layout_admin_bottom.php'; ?>
