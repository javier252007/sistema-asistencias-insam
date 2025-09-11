<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mis Clases</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <style>
    /* Detalles sutiles para la info */
    .meta { font-size: .92rem; line-height: 1.25rem; color: #555; margin: .25rem 0 0; }
    .meta strong { color:#333; }
    .pill { display:inline-block; padding:.15rem .5rem; border-radius:999px; background:#f3f4f6; font-size:.8rem; margin-right:.35rem; }
  </style>
</head>
<body>
<div class="container">
  <h1>Mis Clases</h1>

  <?php if (empty($clases)): ?>
    <p class="subtext">Sin clases asignadas.</p>
  <?php else: ?>
    <div class="cards-wrap">
      <?php
        // Mapeo de día si viene numérico (1-7). Si ya viene texto, se usa tal cual.
        $mapDias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
      ?>
      <?php foreach ($clases as $c): ?>
        <?php
          $diaRaw = $c['dia'] ?? '';
          $diaTxt = is_numeric($diaRaw) ? ($mapDias[(int)$diaRaw] ?? (string)$diaRaw) : (string)$diaRaw;

          $horaIni = substr($c['hora_inicio'] ?? '', 0, 5);
          $horaFin = substr($c['hora_fin'] ?? '', 0, 5);

          $asignatura = $c['asignatura'] ?? null;
          $grupo      = $c['grupo'] ?? null;
          $anio       = $c['anio_lectivo'] ?? null;
          $aula       = $c['aula'] ?? null;
        ?>
        <!-- Reutiliza el show de admin con la ruta para docente -->
        <a class="card" href="index.php?action=docente_clases_show&id=<?= (int)$c['id'] ?>">
          <div class="card-icon">📚</div>

          <h3>
            <?= htmlspecialchars($asignatura ?: ('Clase #'.(int)$c['id'])) ?>
          </h3>

          <!-- Línea principal: Grupo · Día · Horario -->
          <p class="meta">
            <?php if ($grupo): ?>
              <span class="pill"><?= htmlspecialchars($grupo) ?></span>
            <?php endif; ?>

            <?php if ($anio): ?>
              <span class="pill">Año: <?= htmlspecialchars((string)$anio) ?></span>
            <?php endif; ?>

            <?php if ($diaTxt): ?>
              <span class="pill"><?= htmlspecialchars($diaTxt) ?></span>
            <?php endif; ?>

            <?php if ($horaIni || $horaFin): ?>
              <span class="pill"><?= htmlspecialchars(trim($horaIni.' - '.$horaFin)) ?></span>
            <?php endif; ?>

            <?php if ($aula): ?>
              <span class="pill">Aula: <?= htmlspecialchars($aula) ?></span>
            <?php endif; ?>
          </p>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p style="margin-top:1rem">
    <a class="btn small" href="index.php?action=dashboard">⬅ Volver</a>
  </p>
</div>
</body>
</html>