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
  <div class="header-row">
    <h1>Clase #<?= (int)$clase['id'] ?></h1>
    <a class="btn primary"
       href="index.php?action=clases_asistencia&id=<?= (int)$clase['id'] ?>&fecha=<?= htmlspecialchars(date('Y-m-d')) ?>">
      Asistencia y Reporte
    </a>
  </div>

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

  <div class="table-responsive">
    <table class="table w-100">
      <thead>
        <tr>
          <th>NIE</th>
          <th>Nombre</th>
          <th>Estado</th>
          <th>Teléfono</th>
          <th>Correo</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($estudiantes)): ?>
          <tr><td colspan="5" class="text-center muted">No hay estudiantes en este grupo.</td></tr>
        <?php else: ?>
          <?php foreach ($estudiantes as $e): ?>
            <tr>
              <td><?= htmlspecialchars($e['NIE'] ?? '') ?></td>
              <td><?= htmlspecialchars($e['nombre'] ?? '') ?></td>
              <td><span class="badge <?= ($e['estado']==='activo') ? 'ok' : 'off' ?>"><?= htmlspecialchars($e['estado']) ?></span></td>
              <td><?= htmlspecialchars($e['telefono'] ?? '') ?></td>
              <td><?= htmlspecialchars($e['correo'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="mt-10"><a href="index.php?action=clases_index">Volver</a></p>
</div>
</body>
</html>
