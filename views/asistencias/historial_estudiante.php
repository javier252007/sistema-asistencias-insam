<?php // views/asistencias/historial_estudiante.php ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Historial del Estudiante</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- CSS global existente -->
  <link rel="stylesheet" href="css/asistencia.css">
  <!-- CSS del módulo Asistencias -->
  <link rel="stylesheet" href="css/asistencias/asistencias.css">
</head>
<body>
<div class="box">
  <div class="toolbar">
    <a class="btn" href="index.php?action=asistencia_historial">← Volver al historial general</a>
    <form method="get" action="index.php" class="form-inline">
      <input type="hidden" name="action" value="asistencia_historial_estudiante">
      <input type="hidden" name="id" value="<?= (int)$estudiante['id'] ?>">
      <input type="date" name="fecha" value="<?= htmlspecialchars($_GET['fecha'] ?? '') ?>">
      <select name="perPage">
        <?php foreach ([25,50,100,200] as $pp): ?>
          <option value="<?= $pp ?>" <?= ($pp==($result['perPage']??25)?'selected':'') ?>><?= $pp ?>/página</option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary">Filtrar</button>
    </form>
  </div>

  <h1>Historial de: <?= htmlspecialchars($estudiante['nombre'] ?? 'Estudiante') ?></h1>
  <p class="muted">
    NIE: <span class="pill"><?= htmlspecialchars($estudiante['NIE'] ?? '') ?></span>
  </p>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Fecha y hora</th>
          <th>Tipo</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($result['data'])): ?>
        <tr><td colspan="3" class="text-center">Sin registros</td></tr>
      <?php else: ?>
        <?php foreach ($result['data'] as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['fecha_hora']) ?></td>
            <td><span class="badge"><?= htmlspecialchars($r['tipo']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    $page  = (int)($result['page'] ?? 1);
    $pages = (int)($result['pages'] ?? 1);
    $qs = http_build_query([
      'action'  => 'asistencia_historial_estudiante',
      'id'      => (int)$estudiante['id'],
      'fecha'   => $_GET['fecha'] ?? '',
      'perPage' => $result['perPage'] ?? 25,
    ]);
  ?>
  <nav aria-label="Paginación">
    <ul class="pagination">
      <li><a class="btn" href="index.php?<?= $qs ?>&page=<?= max(1,$page-1) ?>">Anterior</a></li>
      <li><span class="btn no-click">Página <?= $page ?> de <?= $pages ?></span></li>
      <li><a class="btn" href="index.php?<?= $qs ?>&page=<?= min($pages,$page+1) ?>">Siguiente</a></li>
    </ul>
  </nav>
</div>
</body>
</html>

