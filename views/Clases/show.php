<?php // views/Clases/show.php ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Clase #<?= (int)$clase['id'] ?></title>
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/clases/clases.css">
</head>
<body>
<div class="container">
  <h1>Clase #<?= (int)$clase['id'] ?></h1>

  <div class="grid mb-15">
    <div><strong>Docente:</strong> <?= htmlspecialchars($clase['docente_nombre'] ?? '—') ?></div>
    <div><strong>Asignatura:</strong> <?= htmlspecialchars($clase['asignatura_nombre'] ?? '—') ?></div>
    <div><strong>Grupo:</strong> <?= htmlspecialchars(trim(($clase['grado'] ?? '').' '.($clase['seccion'] ?? ''))) ?></div>
    <div><strong>Día:</strong> <?= htmlspecialchars($clase['dia'] ?? '') ?></div>
    <div><strong>Hora:</strong> <?= htmlspecialchars(($clase['hora_inicio'] ?? '').' - '.($clase['hora_fin'] ?? '')) ?></div>
    <div><strong>Aula:</strong> <?= htmlspecialchars($clase['aula'] ?? '') ?></div>
    <div><strong>Año lectivo:</strong> <?= htmlspecialchars($clase['anio_lectivo'] ?? '') ?></div>
  </div>

  <h3>Estudiantes del grupo</h3>

  <div class="toolbar">
    <button class="btn" type="button" onclick="toggleChecks(true)">Seleccionar todos</button>
    <button class="btn" type="button" onclick="toggleChecks(false)">Ninguno</button>
    <span id="selCount" class="muted"></span>
  </div>

  <form method="post" action="#" onsubmit="return false;">
    <input type="hidden" name="clase_id" value="<?= (int)$clase['id'] ?>">

    <div class="table-responsive">
      <table class="table w-100">
        <thead>
          <tr>
            <th></th>
            <th>NIE</th>
            <th>Nombre</th>
            <th>Estado</th>
            <th>Teléfono</th>
            <th>Correo</th>
          </tr>
        </thead>
        <tbody id="tb">
          <?php if (empty($estudiantes)): ?>
            <tr><td colspan="6" class="text-center muted">No hay estudiantes en este grupo.</td></tr>
          <?php else: ?>
            <?php foreach ($estudiantes as $e): ?>
              <tr>
                <td><input type="checkbox" name="estudiantes[]" value="<?= (int)$e['id'] ?>" onchange="updateCount()"></td>
                <td><?= htmlspecialchars($e['NIE'] ?? '') ?></td>
                <td><?= htmlspecialchars($e['nombre'] ?? '') ?></td>
                <td>
                  <span class="badge <?= ($e['estado']==='activo') ? 'ok' : 'off' ?>">
                    <?= htmlspecialchars($e['estado']) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($e['telefono'] ?? '') ?></td>
                <td><?= htmlspecialchars($e['correo'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <!-- Botones de acción opcionales -->
    <!-- <button class="btn primary" type="submit">Guardar asistencia</button> -->
  </form>

  <p class="mt-10"><a href="index.php?action=clases_index">Volver</a></p>
</div>

<script>
  function toggleChecks(state){
    document.querySelectorAll('#tb input[type=checkbox]').forEach(cb => cb.checked = state);
    updateCount();
  }
  function updateCount(){
    const total = document.querySelectorAll('#tb input[type=checkbox]').length;
    const sel   = document.querySelectorAll('#tb input[type=checkbox]:checked').length;
    document.getElementById('selCount').textContent = sel + ' seleccionados de ' + total;
  }
  updateCount();
</script>
</body>
</html>

