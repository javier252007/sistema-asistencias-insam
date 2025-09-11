<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Incidencias</title>
<link rel="stylesheet" href="css/dashboard.css"></head>
<body>
<div class="container">
  <h1>Incidencias registradas</h1>
  <div class="cards-wrap" style="margin-top:1rem">
    <?php if (empty($incidencias)): ?>
      <p class="subtext">Aún no registras incidencias.</p>
    <?php else: foreach ($incidencias as $i): ?>
      <div class="card">
        <div class="card-icon">⚠️</div>
        <h3><?= htmlspecialchars($i['tipo'] ?? 'Incidencia') ?></h3>
        <p><?= htmlspecialchars($i['estudiante'] ?? '') ?></p>
        <p><?= htmlspecialchars($i['observacion'] ?? '') ?></p>
        <p class="subtext"><?= htmlspecialchars($i['fecha'] ?? '') ?></p>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <p style="margin-top:1rem"><a class="btn small" href="index.php?action=dashboard">⬅ Volver</a></p>
</div>
</body></html>
