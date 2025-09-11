<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Reportes (Docente)</title>
<link rel="stylesheet" href="css/dashboard.css"></head>
<body>
<div class="container">
  <h1>Reportes del Docente</h1>
  <div class="cards-wrap">
    <div class="card"><div class="card-icon">📚</div><h3>Clases</h3><p><?= (int)($kpis['clases'] ?? 0) ?></p></div>
    <div class="card"><div class="card-icon">👥</div><h3>Estudiantes</h3><p><?= (int)($kpis['estudiantes'] ?? 0) ?></p></div>
    <div class="card"><div class="card-icon">✅</div><h3>Asistencia hoy</h3><p><?= isset($kpis['asistencia_hoy']) && $kpis['asistencia_hoy']!==null ? $kpis['asistencia_hoy'].'%' : '—' ?></p></div>
  </div>
  <p style="margin-top:1rem"><a class="btn small" href="index.php?action=dashboard">⬅ Volver</a></p>
</div>
</body></html>
