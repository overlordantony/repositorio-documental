<?php
// public/index.php — Repositorio público
require_once __DIR__ . '/../src/bootstrap.php';

// Filtros
$filtros = [
    'q'            => trim($_GET['q'] ?? ''),
    'categoria_id' => (int)($_GET['categoria_id'] ?? 0),
    'dependencia_id'=> (int)($_GET['dependencia_id'] ?? 0),
    'anio'         => (int)($_GET['anio'] ?? 0),
];
$pagina = max(1, (int)($_GET['p'] ?? 1));

// Datos
$total      = Document::contarPublico($filtros);
$pag        = paginar($total, PER_PAGE_PUBLIC, $pagina);
$documentos = Document::listarPublico($filtros, PER_PAGE_PUBLIC, $pag['offset']);
$categorias = Category::conConteo();
$anios      = Document::aniosDisponibles();

$pageTitle = 'Repositorio Documental';
include_once TPL_PATH . '/layout_public_top.php';
?>

<div class="layout">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-title">Categorías</div>
      <a href="<?= BASE_URL ?>/" class="sidebar-item <?= $filtros['categoria_id'] === 0 ? 'activo' : '' ?>">
        Todos los documentos
        <span class="badge-n"><?= array_sum(array_column($categorias,'total_docs')) ?></span>
      </a>
      <div class="sidebar-divider"></div>
      <?php foreach ($categorias as $cat): ?>
        <a href="<?= buildUrl(['categoria_id' => $cat['id']]) ?>"
           class="sidebar-item <?= $filtros['categoria_id'] == $cat['id'] ? 'activo' : '' ?>"
           data-cat="<?= $cat['id'] ?>">
          <?= e($cat['nombre']) ?>
          <span class="badge-n"><?= $cat['total_docs'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($anios): ?>
    <div class="sidebar-section">
      <div class="sidebar-title">Año</div>
      <?php foreach ($anios as $a): ?>
        <a href="<?= buildUrl(['anio' => $a['anio']]) ?>"
           class="sidebar-item <?= $filtros['anio'] == $a['anio'] ? 'activo' : '' ?>"
           data-anio="<?= $a['anio'] ?>">
          <?= $a['anio'] ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </aside>

  <!-- CONTENIDO -->
  <main class="main">
    <div class="main-header">
      <div>
        <div class="main-titulo">
          <?php
          $tituloActivo = 'Todos los documentos';
          foreach ($categorias as $c) {
              if ($c['id'] == $filtros['categoria_id']) { $tituloActivo = e($c['nombre']); break; }
          }
          echo $tituloActivo;
          if ($filtros['anio']) echo ' · ' . $filtros['anio'];
          ?>
        </div>
        <div class="main-sub">
          <?= number_format($total) ?> documento<?= $total !== 1 ? 's' : '' ?>
          <?= $filtros['q'] ? ' para "' . e($filtros['q']) . '"' : '' ?>
        </div>
      </div>
    </div>

    <!-- Chips de filtros activos -->
    <?php if ($filtros['q'] || $filtros['categoria_id'] || $filtros['anio']): ?>
    <div class="filtros-activos">
      <?php if ($filtros['q']): ?>
        <a href="<?= buildUrl([], ['q']) ?>" class="chip">"<?= e($filtros['q']) ?>" <span class="x">×</span></a>
      <?php endif; ?>
      <?php if ($filtros['categoria_id']): ?>
        <?php foreach ($categorias as $c): if ($c['id'] == $filtros['categoria_id']): ?>
          <a href="<?= buildUrl([], ['categoria_id']) ?>" class="chip"><?= e($c['nombre']) ?> <span class="x">×</span></a>
        <?php endif; endforeach; ?>
      <?php endif; ?>
      <?php if ($filtros['anio']): ?>
        <a href="<?= buildUrl([], ['anio']) ?>" class="chip"><?= $filtros['anio'] ?> <span class="x">×</span></a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/" class="chip-clear">Limpiar filtros</a>
    </div>
    <?php endif; ?>

    <!-- TABLA -->
    <?php if (empty($documentos)): ?>
      <div class="sin-resultados">
        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p>No se encontraron documentos con los filtros seleccionados.</p>
        <br>
        <a href="<?= BASE_URL ?>/" style="color:var(--azul)">Ver todos los documentos</a>
      </div>
    <?php else: ?>
      <table class="doc-table">
        <thead>
          <tr>
            <th style="width:44px"></th>
            <th>Documento</th>
            <th class="td-dep">Dependencia</th>
            <th class="td-fecha">Fecha</th>
            <th style="width:90px"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($documentos as $doc):
          $tipo = tipoInfo($doc['tipo_archivo']);
        ?>
          <tr>
            <td>
              <div class="tipo-badge" style="background:<?= e($tipo['bg']) ?>;color:<?= e($tipo['color']) ?>">
                <?= $tipo['label'] ?>
              </div>
            </td>
            <td>
              <div class="doc-titulo"><?= e($doc['titulo']) ?></div>
              <div class="doc-meta">
                <span class="cat-badge"
                  style="background:<?= e($doc['color_fondo']) ?>;color:<?= e($doc['color_texto']) ?>">
                  <?= e($doc['categoria_nombre']) ?>
                </span>
                <?php if ($doc['descripcion']): ?>
                  <span><?= e(mb_strimwidth($doc['descripcion'], 0, 90, '…')) ?></span>
                <?php endif; ?>
                <span><?= formatSize($doc['tamanio_kb']) ?></span>
              </div>
            </td>
            <td class="td-dep"><?= e($doc['dependencia_nombre']) ?></td>
            <td class="td-fecha"><?= date('d M Y', strtotime($doc['fecha_publicacion'])) ?></td>
            <td>
              <a href="<?= e(".." . UPLOADS_URL . $doc['archivo']) ?>"
                 target="_blank" rel="noopener"
                 class="btn-abrir">Abrir</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <!-- PAGINACIÓN -->
      <?php if ($pag['totalPaginas'] > 1): ?>
      <div class="paginacion">
        <a href="?<?= http_build_query(array_merge($_GET, ['p' => $pag['pagina'] - 1])) ?>"
           class="pag-btn <?= $pag['pagina'] <= 1 ? 'disabled' : '' ?>">← Anterior</a>
        <?php for ($i = max(1, $pag['pagina']-2); $i <= min($pag['totalPaginas'], $pag['pagina']+2); $i++): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>"
             class="pag-btn <?= $i === $pag['pagina'] ? 'activo' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <span class="pag-info">Página <?= $pag['pagina'] ?> de <?= $pag['totalPaginas'] ?></span>
        <a href="?<?= http_build_query(array_merge($_GET, ['p' => $pag['pagina'] + 1])) ?>"
           class="pag-btn <?= $pag['pagina'] >= $pag['totalPaginas'] ? 'disabled' : '' ?>">Siguiente →</a>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</div>

<?php include_once TPL_PATH . '/layout_public_bottom.php'; ?>
