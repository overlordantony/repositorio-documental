// public/assets/js/admin.js

document.addEventListener('DOMContentLoaded', () => {

  // ── File drop zone ──────────────────────────────
  const dropArea = document.getElementById('drop-area');
  const fileInput = document.getElementById('file-input');
  const fileName  = document.getElementById('file-name');

  if (dropArea && fileInput) {
    dropArea.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
      if (fileInput.files[0]) {
        fileName.textContent = fileInput.files[0].name;
      }
    });

    ['dragenter', 'dragover'].forEach(ev =>
      dropArea.addEventListener(ev, e => {
        e.preventDefault();
        dropArea.classList.add('drag');
      })
    );

    ['dragleave', 'drop'].forEach(ev =>
      dropArea.addEventListener(ev, e => {
        e.preventDefault();
        dropArea.classList.remove('drag');
      })
    );

    dropArea.addEventListener('drop', e => {
      const dt = e.dataTransfer;
      if (dt.files.length) {
        fileInput.files = dt.files;
        fileName.textContent = dt.files[0].name;
      }
    });
  }

  // ── Confirmación de eliminación ─────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm || '¿Confirmas esta acción?')) {
        e.preventDefault();
      }
    });
  });

  // ── Preview de colores en form categorías ───────
  const colorTexto = document.getElementById('color_texto');
  const colorFondo = document.getElementById('color_fondo');
  const preview    = document.getElementById('cat-preview');

  function actualizarPreview() {
    if (!preview) return;
    preview.style.color      = colorTexto?.value ?? '#000';
    preview.style.background = colorFondo?.value ?? '#eee';
  }

  colorTexto?.addEventListener('input', actualizarPreview);
  colorFondo?.addEventListener('input', actualizarPreview);
  actualizarPreview();

  // ── Auto-cerrar alertas tras 4s ─────────────────
  document.querySelectorAll('.alert[data-auto-close]').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, 4000);
  });

  // ── Marcar nav item activo ───────────────────────
  const path = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item').forEach(el => {
    const href = el.getAttribute('href')?.split('/').pop();
    if (href && href === path) el.classList.add('activo');
  });

});
