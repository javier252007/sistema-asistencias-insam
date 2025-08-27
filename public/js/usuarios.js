// public/js/usuarios.js
(function () {
  const input = document.getElementById('usuariosSearch');
  const btn = document.getElementById('btnBuscar');
  const table = document.getElementById('tablaUsuarios');
  if (!input || !table) return;

  const rows = Array.from(table.querySelectorAll('tbody tr'));

  function filtrar() {
    const q = input.value.trim().toLowerCase();
    rows.forEach(tr => {
      const text = tr.innerText.toLowerCase();
      tr.style.display = text.includes(q) ? '' : 'none';
    });
  }

  input.addEventListener('input', filtrar);
  input.addEventListener('keydown', e => { if (e.key === 'Enter') filtrar(); });
  if (btn) btn.addEventListener('click', filtrar);
})();
