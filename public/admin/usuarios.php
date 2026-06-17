<?php
// public/admin/usuarios.php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::require();
Auth::requirePermiso('usuarios.ver');

// ── Eliminar ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    Auth::requirePermiso('usuarios.eliminar');
    csrfCheck();
    $r = User::eliminar((int)($_POST['id'] ?? 0));
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/usuarios.php');
}

// ── Crear usuario ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    Auth::requirePermiso('usuarios.crear');
    csrfCheck();
    $r = User::crear([
        'nombre'   => trim($_POST['nombre'] ?? ''),
        'usuario'  => trim($_POST['usuario'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'rol'      => $_POST['rol'] ?? 'editor',
    ]);
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/usuarios.php');
}

// ── Editar datos del usuario ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar') {
    csrfCheck();
    $target_id = (int)($_POST['id'] ?? 0);

    // Solo superadmin puede editar otros; cualquiera puede editarse a sí mismo
    if ($target_id !== Auth::id() && !Auth::esSuperadmin()) {
        flashSet('error', 'No tienes permiso para editar ese usuario.');
        redirect('/admin/usuarios.php');
    }

    $r = User::editar($target_id, [
        'nombre'  => trim($_POST['nombre'] ?? ''),
        'usuario' => trim($_POST['usuario'] ?? ''),
        'email'   => trim($_POST['email'] ?? ''),
        'rol'     => $_POST['rol'] ?? 'editor',
        'activo'  => $_POST['activo'] ?? 0,
    ]);
    flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    redirect('/admin/usuarios.php');
}

// ── Cambiar contraseña ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_password') {
    csrfCheck();
    $target_id = (int)($_POST['target_id'] ?? 0);
    $nueva     = $_POST['nueva_password'] ?? '';
    $confirmar = $_POST['confirmar_password'] ?? '';

    if ($target_id !== Auth::id() && !Auth::esSuperadmin()) {
        flashSet('error', 'No tienes permiso para cambiar esa contraseña.');
    } elseif ($nueva !== $confirmar) {
        flashSet('error', 'Las contraseñas no coinciden.');
    } else {
        $actual = ($target_id === Auth::id()) ? ($_POST['password_actual'] ?? '') : null;
        $r = User::cambiarPassword($target_id, $nueva, $actual);
        flashSet($r['ok'] ? 'ok' : 'error', $r['msg']);
    }
    redirect('/admin/usuarios.php');
}

$usuarios  = User::all();
$pageTitle = 'Usuarios';
$navActivo = 'usuarios';
include_once TPL_PATH . '/layout_admin_top.php';
?>

<div class="page-title">Usuarios del sistema</div>

<div class="grid-2">

  <!-- ── CREAR USUARIO ── -->
  <?php if (Auth::can('usuarios.crear')): ?>
  <div class="card">
    <div class="card-title">Nuevo usuario</div>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="accion" value="crear">
      <div class="form-group">
        <label>Nombre completo *</label>
        <input type="text" name="nombre" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Usuario * <small style="color:var(--texto-3)">(login)</small></label>
          <input type="text" name="usuario" required autocomplete="off">
        </div>
        <div class="form-group">
          <label>Rol *</label>
          <select name="rol">
            <option value="editor">Editor</option>
            <option value="viewer">Visor</option>
            <?php if (Auth::esSuperadmin()): ?>
              <option value="superadmin">Superadmin</option>
            <?php endif; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Contraseña * <small style="color:var(--texto-3)">(mín. 8 caracteres)</small></label>
        <input type="password" name="password" required minlength="8" autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary btn-full">Crear usuario</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- ── LISTADO ── -->
  <div class="card">
    <div class="card-title">Usuarios registrados (<?= count($usuarios) ?>)</div>
    <table class="tabla">
      <thead>
        <tr><th>Usuario</th><th>Rol</th><th>Último acceso</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($usuarios as $u):
        $puedeEditar = ($u['id'] === Auth::id()) || Auth::esSuperadmin();
      ?>
        <tr>
          <td>
            <div style="font-weight:500"><?= e($u['nombre']) ?></div>
            <div style="font-size:11px;color:var(--texto-2)">
              @<?= e($u['usuario']) ?> · <?= e($u['email']) ?>
            </div>
          </td>
          <td>
            <span class="badge badge-<?= $u['rol'] === 'superadmin' ? 'admin' : $u['rol'] ?>">
              <?= e(labelRol($u['rol'])) ?>
            </span>
            <?php if (!$u['activo']): ?>
              <span class="badge" style="background:#f1efe8;color:#999">Inactivo</span>
            <?php endif; ?>
          </td>
          <td style="font-size:11px;color:var(--texto-2)">
            <?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca' ?>
          </td>
          <td class="acciones">
            <?php if ($puedeEditar): ?>
              <button class="btn btn-sm btn-outline"
                      onclick="abrirEditar(<?= htmlspecialchars(json_encode([
                          'id'      => $u['id'],
                          'nombre'  => $u['nombre'],
                          'usuario' => $u['usuario'],
                          'email'   => $u['email'],
                          'rol'     => $u['rol'],
                          'activo'  => $u['activo'],
                          'es_yo'   => $u['id'] === Auth::id(),
                      ]), ENT_QUOTES) ?>)">
                Editar
              </button>
              <button class="btn btn-sm btn-outline"
                      onclick="abrirPassword(<?= $u['id'] ?>, '<?= e($u['nombre']) ?>', <?= $u['id'] === Auth::id() ? 'true' : 'false' ?>)"
                      style="margin-left:4px">
                🔑
              </button>
            <?php endif; ?>
            <?php if (Auth::can('usuarios.eliminar') && $u['id'] !== Auth::id()): ?>
              <form method="post" style="display:inline;margin-left:4px">
                <?= csrfField() ?>
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm="¿Eliminar usuario @<?= e($u['usuario']) ?>? Esta acción no se puede deshacer.">
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

<!-- ══════════════════════════════════════════════
     MODAL: EDITAR DATOS DEL USUARIO
════════════════════════════════════════════════ -->
<div id="modal-editar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:12px;padding:32px;width:100%;max-width:460px;box-shadow:0 8px 40px rgba(0,0,0,.2)">
    <h3 style="font-family:'Lora',serif;font-size:16px;color:var(--azul);margin-bottom:20px">
      Editar usuario
    </h3>
    <form method="post" id="form-editar">
      <?= csrfField() ?>
      <input type="hidden" name="accion" value="editar">
      <input type="hidden" name="id" id="edit-id">

      <div class="form-group">
        <label>Nombre completo *</label>
        <input type="text" name="nombre" id="edit-nombre" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Usuario <small style="color:var(--texto-3)">(login)</small> *</label>
          <input type="text" name="usuario" id="edit-usuario" required autocomplete="off">
        </div>
        <div class="form-group">
          <label>Rol *</label>
          <select name="rol" id="edit-rol">
            <option value="editor">Editor</option>
            <option value="viewer">Visor</option>
            <?php if (Auth::esSuperadmin()): ?>
              <option value="superadmin">Superadmin</option>
            <?php endif; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" id="edit-email" required>
      </div>

      <!-- Activo solo lo puede cambiar el superadmin y no a sí mismo -->
      <div class="form-group" id="campo-activo" style="display:none">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" name="activo" id="edit-activo" value="1">
          Usuario activo
        </label>
      </div>

      <div style="display:flex;gap:10px;margin-top:14px">
        <button type="submit" class="btn btn-primary" style="flex:1">Guardar cambios</button>
        <button type="button" class="btn btn-outline" onclick="cerrarModal('modal-editar')" style="flex:1">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════════════
     MODAL: CAMBIAR CONTRASEÑA
════════════════════════════════════════════════ -->
<div id="modal-password" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:12px;padding:32px;width:100%;max-width:400px;box-shadow:0 8px 40px rgba(0,0,0,.2)">
    <h3 style="font-family:'Lora',serif;font-size:16px;color:var(--azul);margin-bottom:6px">
      Cambiar contraseña
    </h3>
    <p style="font-size:13px;color:var(--texto-2);margin-bottom:20px" id="pw-subtitulo"></p>
    <form method="post" id="form-password">
      <?= csrfField() ?>
      <input type="hidden" name="accion" value="cambiar_password">
      <input type="hidden" name="target_id" id="pw-target-id">

      <div class="form-group" id="campo-actual-pw">
        <label>Contraseña actual *</label>
        <input type="password" name="password_actual" id="pw-actual" autocomplete="current-password">
      </div>
      <div class="form-group">
        <label>Nueva contraseña * <small style="color:var(--texto-3)">(mín. 8 caracteres)</small></label>
        <input type="password" name="nueva_password" required minlength="8" autocomplete="new-password">
      </div>
      <div class="form-group">
        <label>Confirmar nueva contraseña *</label>
        <input type="password" name="confirmar_password" required minlength="8" autocomplete="new-password">
      </div>

      <div style="display:flex;gap:10px;margin-top:14px">
        <button type="submit" class="btn btn-primary" style="flex:1">Cambiar contraseña</button>
        <button type="button" class="btn btn-outline" onclick="cerrarModal('modal-password')" style="flex:1">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
const esSuperadmin = <?= Auth::esSuperadmin() ? 'true' : 'false' ?>;

function abrirEditar(u) {
  document.getElementById('edit-id').value      = u.id;
  document.getElementById('edit-nombre').value  = u.nombre;
  document.getElementById('edit-usuario').value = u.usuario;
  document.getElementById('edit-email').value   = u.email;
  document.getElementById('edit-rol').value     = u.rol;
  document.getElementById('edit-activo').checked = u.activo == 1;

  // Mostrar campo "activo" solo si superadmin edita a otro
  const campoActivo = document.getElementById('campo-activo');
  campoActivo.style.display = (esSuperadmin && !u.es_yo) ? 'block' : 'none';

  // Deshabilitar cambio de rol si no es superadmin o si se edita a sí mismo siendo el único super
  document.getElementById('edit-rol').disabled = !esSuperadmin;

  document.getElementById('modal-editar').style.display = 'flex';
}

function abrirPassword(id, nombre, esPropioUsuario) {
  document.getElementById('pw-target-id').value = id;
  document.getElementById('pw-subtitulo').textContent = nombre;

  const campoActual = document.getElementById('campo-actual-pw');
  const inputActual = document.getElementById('pw-actual');
  campoActual.style.display = esPropioUsuario ? 'block' : 'none';
  inputActual.required      = esPropioUsuario;

  document.getElementById('form-password').reset();
  document.getElementById('pw-target-id').value = id; // reset limpia hidden, restaurar
  document.getElementById('modal-password').style.display = 'flex';
}

function cerrarModal(id) {
  document.getElementById(id).style.display = 'none';
}

// Cerrar al hacer clic fuera del modal
['modal-editar', 'modal-password'].forEach(id => {
  document.getElementById(id)?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModal(id);
  });
});
</script>

<?php include_once TPL_PATH . '/layout_admin_bottom.php'; ?>
