<?php
// views/asistencias/registro.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registro de Asistencia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/asistencia.css">
</head>
<body>
<div class="box">
  <h1>Registro de Asistencia</h1>
  <p class="muted">Escribe tu <strong>NIE</strong> y selecciona tu nombre para marcar <strong>entrada o salida</strong>.</p>

  <?php if (!empty($mensaje)): ?>
    <div class="flash"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <div class="card">
    <label for="nie">NIE del estudiante</label>
    <input id="nie" class="input" type="text" inputmode="numeric" autocomplete="off" placeholder="Ej: 123456" />

    <div id="lista" class="list" style="display:none;"></div>

    <div id="seleccion" class="card" style="display:none;">
      <div class="muted">Estudiante seleccionado</div>
      <h3 id="sel_nombre" style="margin: 6px 0 4px;"></h3>
      <div class="muted" id="sel_nie"></div>

      <div class="btn-group" style="margin-top:12px;">
        <!-- Form ENTRADA -->
        <form id="formEntrada" method="POST" action="index.php?action=marcar_entrada">
          <input type="hidden" name="estudiante_id" id="estudiante_id_entrada" value="">
          <button class="btn btn-primary" type="submit">Marcar entrada</button>
        </form>

        <!-- Form SALIDA -->
        <form id="formSalida" method="POST" action="index.php?action=marcar_salida">
          <input type="hidden" name="estudiante_id" id="estudiante_id_salida" value="">
          <button class="btn btn-primary" type="submit">Marcar salida</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const $nie = document.getElementById('nie');
  const $lista = document.getElementById('lista');
  const $sel = document.getElementById('seleccion');
  const $selNombre = document.getElementById('sel_nombre');
  const $selNIE = document.getElementById('sel_nie');
  const $estIdEntrada = document.getElementById('estudiante_id_entrada');
  const $estIdSalida = document.getElementById('estudiante_id_salida');

  let timer = null;

  function limpiarSeleccion() {
    $sel.style.display = 'none';
    $selNombre.textContent = '';
    $selNIE.textContent = '';
    $estIdEntrada.value = '';
    $estIdSalida.value = '';
  }

  function renderLista(data) {
    if (!data || !data.length) {
      $lista.style.display = 'none';
      $lista.innerHTML = '';
      return;
    }
    $lista.innerHTML = data.map(row =>
      `<div class="item" data-id="${row.id}" data-nie="${row.NIE}" data-nombre="${row.nombre}">
         <div><strong>${escapeHtml(row.NIE)}</strong> — ${escapeHtml(row.nombre)}</div>
         ${row.estado && row.estado !== 'activo' ? `<div class="muted">Estado: ${escapeHtml(row.estado)}</div>` : ''}
       </div>`
    ).join('');
    $lista.style.display = 'block';

    Array.from($lista.querySelectorAll('.item')).forEach(it => {
      it.addEventListener('click', () => {
        const id = it.getAttribute('data-id');
        const nie = it.getAttribute('data-nie');
        const nombre = it.getAttribute('data-nombre');
        $selNombre.textContent = nombre;
        $selNIE.textContent = 'NIE: ' + nie;
        $estIdEntrada.value = id;
        $estIdSalida.value  = id;
        $sel.style.display = 'block';
        $lista.style.display = 'none';
      });
    });
  }

  function buscar(nie) {
    fetch('index.php?action=buscar_estudiante&nie=' + encodeURIComponent(nie), {
      headers: { 'Accept': 'application/json' }
    })
      .then(r => r.json())
      .then(j => { if (j && j.ok) renderLista(j.data || []); else renderLista([]); })
      .catch(() => renderLista([]));
  }

  $nie.addEventListener('input', () => {
    const val = $nie.value.trim();
    limpiarSeleccion();
    if (timer) clearTimeout(timer);
    if (val.length === 0) { $lista.style.display = 'none'; $lista.innerHTML = ''; return; }
    timer = setTimeout(() => buscar(val), 200);
  });

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  }
})();
</script>
</body>
</html>
