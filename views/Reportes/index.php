<?php
// views/Reportes/index.php

// Cargar lista de clases para el select (reutilizamos el controlador mismo)
$rc = new ReportesController();
$clasesForSelect = $rc->listarClases();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reportes</title>
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/reportes/reportes.css">
</head>
<body>
<div class="container">
  <h1>Reportes</h1>

  <?php if (!empty($_SESSION['error'])): ?>
    <div class="flash"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
  <?php endif; ?>

  <div class="card">
    <h3>Asistencia institucional (por fechas)</h3>
    <form method="post" action="index.php?action=reporte_institucional" class="form-inline">
      <div class="form-row">
        <label for="rango_desde">Desde</label>
        <input id="rango_desde" type="date" name="fecha_desde" required>
      </div>
      <div class="form-row">
        <label for="rango_hasta">Hasta</label>
        <input id="rango_hasta" type="date" name="fecha_hasta" required>
      </div>
      <button class="btn primary" type="submit">Generar</button>
    </form>

    <?php if (!empty($resA) && !empty($resA['ok'])): ?>
      <p class="muted"><strong>Rango:</strong> <?= htmlspecialchars($resA['f1']) ?> a <?= htmlspecialchars($resA['f2']) ?></p>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>NIE</th>
              <th>Estudiante</th>
              <th>Entradas</th>
              <th>Salidas</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($resA['rows'] as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['fecha']) ?></td>
              <td><?= htmlspecialchars($r['NIE']) ?></td>
              <td><?= htmlspecialchars($r['nombre']) ?></td>
              <td><?= (int)$r['entradas'] ?></td>
              <td><?= (int)$r['salidas'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php elseif (!empty($resA['error'])): ?>
      <div class="flash"><?= htmlspecialchars($resA['error']) ?></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>Asistencia por clase (por fechas)</h3>
    <form method="post" action="index.php?action=reporte_clase" class="form-inline">
      <div class="form-row">
        <label for="clase_id">Clase</label>
        <select id="clase_id" name="clase_id" required>
          <option value="">Seleccione...</option>
          <?php foreach ($clasesForSelect as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <label for="c_desde">Desde</label>
        <input id="c_desde" type="date" name="fecha_desde_c" required>
      </div>
      <div class="form-row">
        <label for="c_hasta">Hasta</label>
        <input id="c_hasta" type="date" name="fecha_hasta_c" required>
      </div>
      <button class="btn primary" type="submit">Generar</button>
    </form>

    <?php if (!empty($resC) && !empty($resC['ok'])): ?>
      <?php if (!empty($resC['clase'])): $c=$resC['clase']; ?>
        <p class="muted">
          <strong>Clase:</strong> <?= htmlspecialchars($c['docente'] ?? '') ?> — <?= htmlspecialchars($c['grupo'] ?? '') ?> — <?= htmlspecialchars($c['asignatura'] ?? '(sin asignatura)') ?><br>
          <strong>Horario:</strong> <?= htmlspecialchars(($c['dia'] ?? '').' '.$c['hora_inicio'].' - '.$c['hora_fin'].' / Aula: '.($c['aula'] ?? '')) ?><br>
          <strong>Rango:</strong> <?= htmlspecialchars($resC['f1']) ?> a <?= htmlspecialchars($resC['f2']) ?>
        </p>
      <?php endif; ?>

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>NIE</th>
              <th>Estudiante</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($resC['rows'] as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['fecha']) ?></td>
              <td><?= htmlspecialchars($r['NIE']) ?></td>
              <td><?= htmlspecialchars($r['nombre']) ?></td>
              <td><?= htmlspecialchars($r['estado']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php elseif (!empty($resC['error'])): ?>
      <div class="flash"><?= htmlspecialchars($resC['error']) ?></div>
    <?php endif; ?>
  </div>

  <p class="mt-10"><a class="btn secondary" href="index.php?action=dashboard">Volver</a></p>
</div>
</body>
</html>
