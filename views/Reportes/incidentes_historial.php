<?php // views/Reportes/incidentes_historial.php ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Historial de incidentes</title>
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/clases/clases.css">
</head>
<body>
<div class="container">
  <div class="header-row">
    <h1>Historial de incidentes</h1>
    <a class="btn" href="index.php?action=reportes&clase_id_i=<?= (int)$clase_id ?>&desde_i=<?= urlencode($desde ?? '') ?>&hasta_i=<?= urlencode($hasta ?? '') ?>">Volver</a>
  </div>

  <div class="grid mb-15">
    <div><strong>Clase:</strong> <?= htmlspecialchars($claseSel['label'] ?? ('#'.$clase_id)) ?></div>
    <div><strong>Estudiante:</strong> <?= htmlspecialchars($estudianteNombre ?? ('ID '.$estudianteId)) ?></div>
  </div>

  <div class="table-responsive">
    <table class="table w-100">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Hora</th>
          <th>Tipo</th>
          <th>Observación</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($historial)): ?>
          <tr><td colspan="4" class="text-center muted">Sin incidentes en el rango seleccionado.</td></tr>
        <?php else: foreach ($historial as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['fecha']) ?></td>
            <td><?= htmlspecialchars($r['hora'] ?? '') ?></td>
            <td><span class="badge tag"><?= htmlspecialchars($r['tipo'] ?? '—') ?></span></td>
            <td><?= htmlspecialchars($r['observacion'] ?? '') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
