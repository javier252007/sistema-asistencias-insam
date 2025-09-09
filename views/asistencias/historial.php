<?php // views/asistencias/historial.php ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Historial de Asistencias</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="css/asistencia.css">
  <link rel="stylesheet" href="css/asistencias/asistencias.css">
</head>
<body>
<div class="box">
  <div class="toolbar">
    <a class="btn" href="index.php?action=asistencia_registro">← Volver al registro</a>
    <form method="get" action="index.php" class="form-inline">
      <input type="hidden" name="action" value="asistencia_historial">
      <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Buscar por nombre o NIE">
      <input type="date" name="fecha" value="<?= htmlspecialchars($fecha ?? '') ?>">
      <select name="perPage">
        <?php foreach ([25,50,100,200] as $pp): ?>
          <option value="<?= $pp ?>" <?= ($pp==($result['perPage']??25)?'selected':'') ?>><?= $pp ?>/página</option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary">Filtrar</button>
    </form>
  </div>

  <h1>Historial de Asistencias</h1>
  <p class="muted">
    Se muestran <strong><?= (int)$result['total'] ?></strong> estudiantes.
    Página <?= (int)$result['page'] ?> de <?= (int)$result['pages'] ?>.
  </p>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Fecha y hora (última)</th>
          <th>NIE</th>
          <th>Estudiante</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($result['data'])): ?>
        <tr><td colspan="5" class="text-center">Sin registros</td></tr>
      <?php else: ?>
        <?php foreach ($result['data'] as $r): ?>
          <tr>
            <td><?= (int)$r['estudiante_id'] ?></td>
            <td><?= htmlspecialchars($r['ultima_fecha'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['NIE']) ?></td>
            <td><?= htmlspecialchars($r['estudiante']) ?></td>
            <td>
              <a class="btn-sm" href="index.php?action=asistencia_historial_estudiante&id=<?= (int)$r['estudiante_id'] ?>">Ver</a>
            </td>
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
      'action'  => 'asistencia_historial',
      'q'       => $q ?? '',
      'fecha'   => $fecha ?? '',
      'perPage' => $result['perPage'] ?? 25,
    ]);
  ?>
  <nav aria-label="Paginación">
    <ul class="pagination">
      <li><a class="btn-sm" href="index.php?<?= $qs ?>&page=<?= max(1,$page-1) ?>">Anterior</a></li>
      <li><span class="btn-sm no-click">Página <?= $page ?> de <?= $pages ?></span></li>
      <li><a class="btn-sm" href="index.php?<?= $qs ?>&page=<?= min($pages,$page+1) ?>">Siguiente</a></li>
    </ul>
  </nav>
</div>
</body>
</html>
