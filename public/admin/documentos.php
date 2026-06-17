<?php
// public/admin/documentos.php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::require();
Auth::requirePermiso('documentos.ver');

// ── Eliminar ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    Auth::requirePermiso('documentos.eliminar');
    csrfCheck();
    $r = Document::eliminar((int)($_POST['id'] ?? 0));
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/documentos.php');
}

// ── Subir nuevo ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    Auth::requirePermiso('documentos.subir');
    csrfCheck();
    $r = Document::crear([
        'titulo'            => trim($_POST['titulo'] ?? ''),
        'descripcion'       => trim($_POST['descripcion'] ?? ''),
        'categoria_id'      => (int)($_POST['categoria_id'] ?? 0),
        'dependencia_id'    => (int)($_POST['dependencia_id'] ?? 0),
        'fecha_publicacion' => $_POST['fecha_publicacion'] ?? date('Y-m-d'),
    ], $_FILES['archivo'] ?? []);
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/documentos.php');
}

// ── Editar ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar') {
    Auth::requirePermiso('documentos.subir');
    csrfCheck();
    // Archivo es opcional en edición
    $file = (!empty($_FILES['archivo_edit']['tmp_name']))
        ? $_FILES['archivo_edit']
        : null;
    $r = Document::editar((int)($_POST['id'] ?? 0), [
        'titulo'            => trim($_POST['titulo'] ?? ''),
        'descripcion'       => trim($_POST['descripcion'] ?? ''),
        'categoria_id'      => (int)($_POST['categoria_id'] ?? 0),
        'dependencia_id'    => (int)($_POST['dependencia_id'] ?? 0),
        'fecha_publicacion' => $_POST['fecha_publicacion'] ?? date('Y-m-d'),
        'activo'            => $_POST['activo'] ?? 0,
    ], $file);
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/documentos.php');
}

// ── Listar ────────────────────────────────────────────────
$filtros = [
    'q'            => trim($_GET['q'] ?? ''),
    'categoria_id' => (int)($_GET['categoria_id'] ?? 0),
    'anio'         => (int)($_GET['anio'] ?? 0),
];
$pagina       = max(1, (int)($_GET['p'] ?? 1));
$total        = Document::contarAdmin($filtros);
$pag          = paginar($total, PER_PAGE_ADMIN, $pagina);
$docs         = Document::listarAdmin($filtros, PER_PAGE_ADMIN, $pag['offset']);
$categorias   = Category::all(true);
$dependencias = Dependency::all(true);

$pageTitle = 'Documentos';
$navActivo = 'documentos';
include_once TPL_PATH . '/layout_admin_top.php';
?>

<div class="page-title">Documentos <span>(<?= number_format($total) ?> en total)</span></div>

<div class="grid-2">

  <!-- ── FORMULARIO SUBIR ── -->
  <?php if (Auth::can('documentos.subir')): ?>
  <div class="card">
    <div class="card-title">Subir nuevo documento</div>
    <form method="post" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="accion" value="crear">
      <div class="form-group">
        <label>Título *</label>
        <input type="text" name="titulo" placeholder="Ej: Resolución 0234 de 2024" required>
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <textarea name="descripcion" placeholder="Resumen del documento..."></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Categoría *</label>
          <select name="categoria_id" required>
            <option value="">— Seleccionar —</option>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Dependencia *</label>
          <select name="dependencia_id" required>
            <option value="">— Seleccionar —</option>
            <?php foreach ($dependencias as $d): ?>
              <option value="<?= $d['id'] ?>">
                <?= e($d['nombre']) ?><?= $d['sigla'] ? ' (' . $d['sigla'] . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Fecha de publicación</label>
        <input type="date" name="fecha_publicacion" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group">
        <label>Archivo *</label>
        <div class="file-drop" id="drop-area">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5">
            <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
          </svg>
          <p>Clic o arrastra el archivo aquí</p>
          <small>PDF, DOC, DOCX, PPT, PPTX — máx. <?= MAX_UPLOAD_MB ?> MB</small>
          <div class="file-name" id="file-name"></div>
        </div>
        <input type="file" id="file-input" name="archivo"
               accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Publicar documento</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- ── LISTADO ── -->
  <div class="card">
    <div class="card-title">Documentos publicados</div>

    <form method="get" class="filtros">
      <input type="text" name="q" placeholder="Buscar..." value="<?= e($filtros['q']) ?>">
      <select name="categoria_id">
        <option value="">Todas las categorías</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $filtros['categoria_id'] == $c['id'] ? 'selected' : '' ?>>
            <?= e($c['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Filtrar</button>
      <?php if ($filtros['q'] || $filtros['categoria_id']): ?>
        <a href="<?= BASE_URL ?>/admin/documentos.php" class="btn btn-outline">Limpiar</a>
      <?php endif; ?>
    </form>

    <?php if (empty($docs)): ?>
      <p style="color:var(--texto-2);text-align:center;padding:30px">No hay documentos.</p>
    <?php else: ?>
    <table class="tabla">
      <thead>
        <tr><th></th><th>Documento</th><th>Subido por</th><th>Fecha</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($docs as $d):
        $tipo = tipoInfo($d['tipo_archivo']);
      ?>
        <tr>
          <td>
            <div class="tipo-b" style="background:<?= $tipo['bg'] ?>;color:<?= $tipo['color'] ?>">
              <?= $tipo['label'] ?>
            </div>
          </td>
          <td>
            <div style="font-size:13px;font-weight:500"><?= e(mb_strimwidth($d['titulo'], 0, 55, '…')) ?></div>
            <div style="font-size:11px;color:var(--texto-2)">
              <?= e($d['categoria_nombre']) ?> · <?= e($d['dependencia_nombre']) ?> · <?= formatSize($d['tamanio_kb']) ?>
              <?php if (!$d['activo']): ?>
                <span style="color:#c00;font-weight:500"> · Oculto</span>
              <?php endif; ?>
            </div>
          </td>
          <td style="font-size:12px;color:var(--texto-2)"><?= e($d['subido_por_nombre'] ?? '—') ?></td>
          <td style="font-size:12px;color:var(--texto-2);white-space:nowrap">
            <?= date('d/m/Y', strtotime($d['fecha_publicacion'])) ?>
          </td>
          <td class="acciones">
            <?php if (Auth::can('documentos.subir')): ?>
              <button class="btn btn-sm btn-outline"
                      onclick="editarDoc(<?= htmlspecialchars(json_encode([
                          'id'               => $d['id'],
                          'titulo'           => $d['titulo'],
                          'descripcion'      => $d['descripcion'] ?? '',
                          'categoria_id'     => $d['categoria_id'],
                          'dependencia_id'   => $d['dependencia_id'],
                          'fecha_publicacion'=> $d['fecha_publicacion'],
                          'activo'           => $d['activo'],
                          'archivo'          => $d['archivo'],
                      ]), ENT_QUOTES) ?>)">
                Editar
              </button>
            <?php endif; ?>
            <?php if (Auth::can('documentos.eliminar')): ?>
              <form method="post" style="display:inline">
                <?= csrfField() ?>
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm="¿Eliminar «<?= e($d['titulo']) ?>»? No se puede deshacer.">
                  Eliminar
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($pag['totalPaginas'] > 1): ?>
    <div class="pag">
      <a href="?p=<?= $pag['pagina']-1 ?>&q=<?= e($filtros['q']) ?>&categoria_id=<?= $filtros['categoria_id'] ?>"
         class="<?= $pag['pagina'] <= 1 ? 'dis' : '' ?>">← Ant</a>
      <?php for ($i = max(1, $pag['pagina']-2); $i <= min($pag['totalPaginas'], $pag['pagina']+2); $i++): ?>
        <a href="?p=<?= $i ?>&q=<?= e($filtros['q']) ?>&categoria_id=<?= $filtros['categoria_id'] ?>"
           class="<?= $i === $pag['pagina'] ? 'on' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <a href="?p=<?= $pag['pagina']+1 ?>&q=<?= e($filtros['q']) ?>&categoria_id=<?= $filtros['categoria_id'] ?>"
         class="<?= $pag['pagina'] >= $pag['totalPaginas'] ? 'dis' : '' ?>">Sig →</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ── MODAL EDITAR DOCUMENTO ── -->
<div id="modal-editar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,.2)">
    <h3 style="font-family:'Lora',serif;font-size:16px;color:var(--azul);margin-bottom:20px">
      Editar documento
    </h3>
    <form method="post" enctype="multipart/form-data" id="form-editar">
      <?= csrfField() ?>
      <input type="hidden" name="accion" value="editar">
      <input type="hidden" name="id" id="edit-id">

      <div class="form-group">
        <label>Título *</label>
        <input type="text" name="titulo" id="edit-titulo" required>
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <textarea name="descripcion" id="edit-descripcion"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Categoría *</label>
          <select name="categoria_id" id="edit-categoria" required>
            <option value="">— Seleccionar —</option>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Dependencia *</label>
          <select name="dependencia_id" id="edit-dependencia" required>
            <option value="">— Seleccionar —</option>
            <?php foreach ($dependencias as $d): ?>
              <option value="<?= $d['id'] ?>">
                <?= e($d['nombre']) ?><?= $d['sigla'] ? ' (' . $d['sigla'] . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Fecha de publicación</label>
          <input type="date" name="fecha_publicacion" id="edit-fecha">
        </div>
        <div class="form-group" style="padding-top:22px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" name="activo" id="edit-activo" value="1">
            Visible en el repositorio
          </label>
        </div>
      </div>

      <div class="form-group">
        <label>Reemplazar archivo <small style="color:var(--texto-3)">(opcional — si no seleccionas, se mantiene el actual)</small></label>
        <div id="edit-archivo-actual" style="font-size:12px;color:var(--texto-2);margin-bottom:8px;padding:8px;background:var(--bg);border-radius:5px">
          Archivo actual: <strong id="edit-archivo-nombre"></strong>
        </div>
        <div class="file-drop" id="drop-area-edit">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5">
            <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
          </svg>
          <p>Clic o arrastra para reemplazar</p>
          <small>PDF, DOC, DOCX, PPT, PPTX — máx. <?= MAX_UPLOAD_MB ?> MB</small>
          <div class="file-name" id="edit-file-name"></div>
        </div>
        <input type="file" id="file-input-edit" name="archivo_edit"
               accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx">
      </div>

      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary" style="flex:1">Guardar cambios</button>
        <button type="button" class="btn btn-outline" onclick="cerrarModal()" style="flex:1">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
function editarDoc(d) {
  document.getElementById('edit-id').value           = d.id;
  document.getElementById('edit-titulo').value       = d.titulo;
  document.getElementById('edit-descripcion').value  = d.descripcion;
  document.getElementById('edit-categoria').value    = d.categoria_id;
  document.getElementById('edit-dependencia').value  = d.dependencia_id;
  document.getElementById('edit-fecha').value        = d.fecha_publicacion;
  document.getElementById('edit-activo').checked     = d.activo == 1;
  document.getElementById('edit-archivo-nombre').textContent = d.archivo;
  document.getElementById('edit-file-name').textContent = '';
  document.getElementById('file-input-edit').value = '';
  document.getElementById('modal-editar').style.display = 'flex';
}

function cerrarModal() {
  document.getElementById('modal-editar').style.display = 'none';
}

document.getElementById('modal-editar').addEventListener('click', function(e) {
  if (e.target === this) cerrarModal();
});

// Drop zone del modal
const dropEdit  = document.getElementById('drop-area-edit');
const fileEdit  = document.getElementById('file-input-edit');
const nameEdit  = document.getElementById('edit-file-name');

dropEdit?.addEventListener('click', () => fileEdit.click());
fileEdit?.addEventListener('change', () => {
  if (fileEdit.files[0]) nameEdit.textContent = '✓ ' + fileEdit.files[0].name;
});
['dragenter','dragover'].forEach(ev => dropEdit?.addEventListener(ev, e => {
  e.preventDefault(); dropEdit.classList.add('drag');
}));
['dragleave','drop'].forEach(ev => dropEdit?.addEventListener(ev, e => {
  e.preventDefault(); dropEdit.classList.remove('drag');
}));
dropEdit?.addEventListener('drop', e => {
  fileEdit.files = e.dataTransfer.files;
  if (fileEdit.files[0]) nameEdit.textContent = '✓ ' + fileEdit.files[0].name;
});
</script>

<?php include_once TPL_PATH . '/layout_admin_bottom.php'; ?>
