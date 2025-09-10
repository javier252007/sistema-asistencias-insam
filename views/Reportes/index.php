<?php // views/Reportes/index.php ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reportes</title>
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/clases/clases.css">
</head>
<body>
<div class="container">
  <div class="header-row">
    <h1>Reportes</h1>
    <a class="btn" href="index.php?action=dashboard">Volver</a>
  </div>

  <!-- Panel 1: ASISTENCIA POR CLASE -->
  <div class="chk" style="display:block;margin-bottom:1rem;">
    <h3 style="margin:.25rem 0;">Asistencia por clase (por fechas)</h3>
    <p class="muted" style="margin:.25rem 0 .6rem 0">
      Selecciona una clase y un rango para listar las asistencias registradas.
    </p>

    <form method="get" action="index.php">
      <input type="hidden" name="action" value="reportes">
      <div class="grid">
        <label>Clase
          <select name="clase_id_a" required>
            <option value="">— Seleccione… —</option>
            <?php foreach ($clases as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (!empty($claseSelA) && (int)$claseSelA['id']===(int)$c['id'])?'selected':'' ?>>
                <?= htmlspecialchars($c['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Desde
          <input type="date" name="desde_a" value="<?= htmlspecialchars($desde_a ?? '') ?>">
        </label>
        <label>Hasta
          <input type="date" name="hasta_a" value="<?= htmlspecialchars($hasta_a ?? '') ?>">
        </label>
      </div>
      <div class="mt-06">
        <button class="btn primary" type="submit">Generar</button>
      </div>
    </form>
  </div>

  <?php if (!empty($claseSelA)): ?>
    <h3 class="mt-10">Asistencia — <?= htmlspecialchars($claseSelA['label']) ?></h3>

    <div class="table-responsive">
      <table class="table w-100">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Estudiante</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($asisResultados)): ?>
            <tr><td colspan="4" class="text-center muted">Sin registros en el rango seleccionado.</td></tr>
          <?php else: foreach ($asisResultados as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['fecha']) ?></td>
              <td><?= htmlspecialchars($r['hora'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['estudiante'] ?? ('ID '.$r['estudiante_id'])) ?></td>
              <td><span class="badge tag"><?= htmlspecialchars(ucfirst($r['estado'] ?? '')) ?></span></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- Panel 2: INCIDENTES POR CLASE (RESUMEN) -->
  <div class="chk" style="display:block;margin:1.25rem 0 .5rem 0;">
    <h3 style="margin:.25rem 0;">Incidentes por clase (por fechas)</h3>
    <p class="muted" style="margin:.25rem 0 .6rem 0">
      Selecciona una clase y un rango de fechas. Se mostrará <strong>una fila por estudiante</strong> con su total y el último incidente. Usa “Ver historial” para ver el detalle.
    </p>

    <form method="get" action="index.php">
      <input type="hidden" name="action" value="reportes">
      <div class="grid">
        <label>Clase
          <select name="clase_id_i" required>
            <option value="">— Seleccione… —</option>
            <?php foreach ($clases as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (!empty($claseSelI) && (int)$claseSelI['id']===(int)$c['id'])?'selected':'' ?>>
                <?= htmlspecialchars($c['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Desde
          <input type="date" name="desde_i" value="<?= htmlspecialchars($desde_i ?? '') ?>">
        </label>
        <label>Hasta
          <input type="date" name="hasta_i" value="<?= htmlspecialchars($hasta_i ?? '') ?>">
        </label>
      </div>
      <div class="mt-06">
        <button class="btn primary" type="submit">Generar</button>
      </div>
    </form>
  </div>

  <?php if (!empty($claseSelI)): ?>
    <h3 class="mt-10">Incidentes (resumen) — <?= htmlspecialchars($claseSelI['label']) ?></h3>

    <div class="table-responsive">
      <table class="table w-100">
        <thead>
          <tr>
            <th>Estudiante</th>
            <th>Total</th>
            <th>Última fecha</th>
            <th>Última hora</th>
            <th style="width:120px">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($incResumen)): ?>
            <tr><td colspan="5" class="text-center muted">Sin incidentes en el rango seleccionado.</td></tr>
          <?php else: foreach ($incResumen as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['estudiante'] ?? ('ID '.$r['estudiante_id'])) ?></td>
              <td><span class="badge tag"><?= (int)$r['total'] ?></span></td>
              <td><?= htmlspecialchars($r['ultima_fecha'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['ultima_hora'] ?? '') ?></td>
              <td>
                <a class="btn" href="index.php?action=reporte_incidentes_historial&clase_id=<?= (int)$claseSelI['id'] ?>&estudiante_id=<?= (int)$r['estudiante_id'] ?>&desde=<?= urlencode($desde_i ?? '') ?>&hasta=<?= urlencode($hasta_i ?? '') ?>">Ver historial</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>
</body>
</html>

