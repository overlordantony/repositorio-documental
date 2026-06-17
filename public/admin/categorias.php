<?php
// public/admin/categorias.php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::require();
Auth::requirePermiso('categorias.ver');

// ── Eliminar ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    Auth::requirePermiso('categorias.eliminar');
    csrfCheck();
    $r = Category::eliminar((int)($_POST['id'] ?? 0));
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/categorias.php');
}

// ── Crear ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    Auth::requirePermiso('categorias.crear');
    csrfCheck();
    $r = Category::crear([
        'nombre'      => trim($_POST['nombre'] ?? ''),
        'color_texto' => $_POST['color_texto'] ?? '#185FA5',
        'color_fondo' => $_POST['color_fondo'] ?? '#E6F1FB',
        'orden'       => (int)($_POST['orden'] ?? 0),
    ]);
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/categorias.php');
}

// ── Editar ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar') {
    Auth::requirePermiso('categorias.editar');
    csrfCheck();
    $r = Category::editar((int)($_POST['id'] ?? 0), [
        'nombre'      => trim($_POST['nombre'] ?? ''),
        'color_texto' => $_POST['color_texto'] ?? '#185FA5',
        'color_fondo' => $_POST['color_fondo'] ?? '#E6F1FB',
        'orden'       => (int)($_POST['orden'] ?? 0),
        'activo'      => (int)!empty($_POST['activo']),
    ]);
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/categorias.php');
}

$categorias = Category::all();
$pageTitle  = 'Categorías';
$navActivo  = 'categorias';
include_once TPL_PATH . '/layout_admin_top.php';
?>

<div class="page-title">Categorías</div>

<div class="grid-2">

  <!-- ── CREAR ── -->
  <?php if (Auth::can('categorias.crear')): ?>
  <div class="card">
    <div class="card-title">Nueva categoría</div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="accion" value="crear">
      <div class="form-group">
        <label>Nombre *</label>
        <input type="text" name="nombre" required placeholder="Ej: Actas">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Color texto</label>
          <input type="color" name="color_texto" value="#185FA5" id="color_texto">
        </div>
        <div class="form-group">
          <label>Color fondo</label>
          <input type="color" name="color_fondo" value="#E6F1FB" id="color_fondo">
        </div>
      </div>
      <div class="form-group">
        <label>Previsualización</label><br>
        <span class="badge" id="cat-preview"
              style="color:#185FA5;background:#E6F1FB;font-size:13px;padding:5px 14px">
          Nombre categoría
        </span>
      </div>
      <div class="form-group">
        <label>Orden (menor = primero)</label>
        <input type="number" name="orden" value="0" style="max-width:100px">
      </div>
      <button type="submit" class="btn btn-primary btn-full">Crear categoría</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- ── LISTADO ── -->
  <div class="card">
    <div class="card-title">Categorías (<?= count($categorias) ?>)</div>
    <table class="tabla">
      <thead>
        <tr><th>Nombre</th><th>Colores</th><th>Orden</th><th>Estado</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($categorias as $c): ?>
        <tr>
          <td>
            <span class="badge"
                  style="color:<?= e($c['color_texto']) ?>;background:<?= e($c['color_fondo']) ?>">
              <?= e($c['nombre']) ?>
            </span>
          </td>
          <td>
            <span class="color-preview" style="background:<?= e($c['color_texto']) ?>"
                  title="Texto: <?= e($c['color_texto']) ?>"></span>
            <span class="color-preview" style="background:<?= e($c['color_fondo']) ?>"
                  title="Fondo: <?= e($c['color_fondo']) ?>"></span>
          </td>
          <td style="font-size:12px"><?= $c['orden'] ?></td>
          <td>
            <span class="badge" style="<?= $c['activo']
              ? 'background:#eaf3de;color:#3b6d11'
              : 'background:#f1efe8;color:#999' ?>">
              <?= $c['activo'] ? 'Activa' : 'Inactiva' ?>
            </span>
          </td>
          <td class="acciones">
            <?php if (Auth::can('categorias.editar')): ?>
              <button class="btn btn-sm btn-outline"
                      onclick="editarCategoria(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)">
                Editar
              </button>
            <?php endif; ?>
            <?php if (Auth::can('categorias.eliminar')): ?>
              <form method="post" style="display:inline">
                <?= csrfField() ?>
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm="¿Eliminar «<?= e($c['nombre']) ?>»?">
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
  <div style="background:#fff;border-radius:12px;padding:32px;width:100%;max-width:440px;margin:16px;box-shadow:0 8px 40px rgba(0,0,0,.2)">
    <h3 style="font-family:'Lora',serif;font-size:16px;color:var(--azul);margin-bottom:20px">
      Editar categoría
    </h3>
    <form method="post" id="form-editar">
      <?= csrfField() ?>
      <input type="hidden" name="accion" value="editar">
      <input type="hidden" name="id" id="edit-id">

      <div class="form-group">
        <label>Nombre *</label>
        <input type="text" name="nombre" id="edit-nombre" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Color texto</label>
          <input type="color" name="color_texto" id="edit-color-texto">
        </div>
        <div class="form-group">
          <label>Color fondo</label>
          <input type="color" name="color_fondo" id="edit-color-fondo">
        </div>
      </div>
      <div class="form-group">
        <label>Previsualización</label><br>
        <span class="badge" id="edit-preview"
              style="font-size:13px;padding:5px 14px">Nombre</span>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Orden</label>
          <input type="number" name="orden" id="edit-orden" style="max-width:100px">
        </div>
        <div class="form-group" style="padding-top:22px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" name="activo" id="edit-activo" value="1">
            Activa
          </label>
        </div>
      </div>

      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary" style="flex:1">Guardar cambios</button>
        <button type="button" class="btn btn-outline" onclick="cerrarModal()" style="flex:1">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
// Preview en formulario de crear
const ctexto = document.getElementById('color_texto');
const cfondo  = document.getElementById('color_fondo');
const prev    = document.getElementById('cat-preview');
function actualizarPreview() {
  if (!prev) return;
  prev.style.color      = ctexto?.value;
  prev.style.background = cfondo?.value;
}
ctexto?.addEventListener('input', actualizarPreview);
cfondo?.addEventListener('input', actualizarPreview);
actualizarPreview();

// Preview en modal de editar
const eTexto = document.getElementById('edit-color-texto');
const eFondo  = document.getElementById('edit-color-fondo');
const ePrev   = document.getElementById('edit-preview');
function actualizarEditPreview() {
  ePrev.style.color      = eTexto.value;
  ePrev.style.background = eFondo.value;
  ePrev.textContent      = document.getElementById('edit-nombre').value || 'Nombre';
}
eTexto?.addEventListener('input', actualizarEditPreview);
eFondo?.addEventListener('input', actualizarEditPreview);
document.getElementById('edit-nombre')?.addEventListener('input', actualizarEditPreview);

function editarCategoria(c) {
  document.getElementById('edit-id').value          = c.id;
  document.getElementById('edit-nombre').value      = c.nombre;
  document.getElementById('edit-color-texto').value = c.color_texto;
  document.getElementById('edit-color-fondo').value = c.color_fondo;
  document.getElementById('edit-orden').value       = c.orden;
  document.getElementById('edit-activo').checked    = c.activo == 1;
  actualizarEditPreview();
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
