// public/assets/js/public.js

document.addEventListener('DOMContentLoaded', () => {
  // Resaltar item activo en sidebar según URL actual
  const params = new URLSearchParams(window.location.search);
  const cat    = params.get('cat');
  const anio   = params.get('anio');

  document.querySelectorAll('.sidebar-item[data-cat]').forEach(el => {
    if (el.dataset.cat === cat) el.classList.add('activo');
  });
  document.querySelectorAll('.sidebar-item[data-anio]').forEach(el => {
    if (el.dataset.anio === anio) el.classList.add('activo');
  });
});
