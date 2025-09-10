<?php // views/Clases/asistencia.php ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Asistencia y Reporte – Clase #<?= (int)$clase['id'] ?></title>
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/clases/clases.css">
</head>
<body>
<div class="container">

  <?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="mb-15" style="background:#ecfeff;border:1px solid #22d3ee;color:#0e7490;padding:.6rem .8rem;border-radius:.45rem;">
      <?= htmlspecialchars($_SESSION['flash_msg']) ?>
    </div>
    <?php unset($_SESSION['flash_msg']); ?>
  <?php endif; ?>

  <div class="header-row">
    <h1>Asistencia y Reporte – Clase #<?= (int)$clase['id'] ?></h1>
    <div style="display:flex;gap:.5rem">
      <a class="btn" href="index.php?action=clases_show&id=<?= (int)$clase['id'] ?>">Volver a la clase</a>
    </div>
  </div>

  <div class="grid mb-15">
    <div><strong>Docente:</strong> <?= htmlspecialchars($clase['docente_nombre'] ?? '—') ?></div>
    <div><strong>Asignatura:</strong> <?= htmlspecialchars($clase['asignatura_nombre'] ?? '—') ?></div>
    <div><strong>Grupo:</strong> <?= htmlspecialchars(trim(($clase['grado'] ?? '').' '.($clase['seccion'] ?? ''))) ?></div>
    <div><strong>Día:</strong> <?= htmlspecialchars($clase['dia'] ?? '') ?></div>
    <div><strong>Hora:</strong> <?= htmlspecialchars(($clase['hora_inicio'] ?? '').' - '.($clase['hora_fin'] ?? '')) ?></div>
    <div><strong>Aula:</strong> <?= htmlspecialchars($clase['aula'] ?? '') ?></div>
  </div>

  <?php
    $fecha = $fecha ?? ($_GET['fecha'] ?? date('Y-m-d'));
    $asistencias = $asistencias ?? [];
    $resumenAsis = $resumenAsis ?? ['presente'=>0,'ausente'=>0,'justificado'=>0];
    $tiposFalta  = $tiposFalta  ?? [1=>'Leve',2=>'Grave',3=>'Muy grave'];
    $faltasDia   = $faltasDia   ?? [];
  ?>

  <!-- Filtro de fecha + Resumen -->
  <form method="get" action="index.php" class="header-row">
    <div style="display:flex;gap:.5rem;align-items:center">
      <input type="hidden" name="action" value="clases_asistencia">
      <input type="hidden" name="id" value="<?= (int)$clase['id'] ?>">
      <label><strong>Fecha:</strong></label>
      <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
      <button class="btn" type="submit">Cambiar</button>
    </div>
    <div class="muted">
      <span class="badge tag">Presente: <?= (int)$resumenAsis['presente'] ?></span>
      <span class="badge tag">Ausente: <?= (int)$resumenAsis['ausente'] ?></span>
      <span class="badge tag">Justificado: <?= (int)$resumenAsis['justificado'] ?></span>
    </div>
  </form>

  <!-- Acción MASIVA: aplica a TODOS sin checkboxes -->
  <?php if (!empty($estudiantes)): ?>
  <form method="post" action="index.php?action=asistencias_store" class="mt-06">
    <input type="hidden" name="clase_id" value="<?= (int)$clase['id'] ?>">
    <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
    <?php foreach ($estudiantes as $e): ?>
      <input type="hidden" name="estudiante_ids[]" value="<?= (int)$e['id'] ?>">
    <?php endforeach; ?>
    <div class="header-row">
      <div>
        <strong>Acción masiva:</strong>
        <select name="tipo_global" required>
          <option value="">-- tipo de asistencia --</option>
          <option value="presente">Presente</option>
          <option value="ausente">Ausente</option>
          <option value="justificado">Justificado</option>
        </select>
        <input type="text" name="observacion" placeholder="Observación (opcional)" style="min-width:260px">
      </div>
      <button class="btn primary" type="submit">Marcar a todos</button>
    </div>
  </form>
  <?php endif; ?>

  <h3 class="mt-10">Marcar por estudiante</h3>

  <div class="table-responsive">
    <table class="table w-100">
      <thead>
        <tr>
          <th>NIE</th>
          <th>Nombre</th>
          <th>Asistencia</th>
          <th>Obs.</th>
          <th>Hoy</th>
          <th>Falta</th>
          <th style="width:120px">Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($estudiantes)): ?>
          <tr><td colspan="7" class="text-center muted">No hay estudiantes en este grupo.</td></tr>
        <?php else: ?>
          <?php foreach ($estudiantes as $e):
            $eid = (int)$e['id'];
            $asisHoy = $asistencias[$eid]['tipo'] ?? null;
            $obsHoy  = $asistencias[$eid]['observacion'] ?? null;
          ?>
            <tr>
              <td><?= htmlspecialchars($e['NIE'] ?? '') ?></td>
              <td><?= htmlspecialchars($e['nombre'] ?? '') ?></td>

              <!-- Formulario POR FILA -->
              <form class="inline" method="post" action="index.php?action=asistencias_store">
                <input type="hidden" name="clase_id" value="<?= (int)$clase['id'] ?>">
                <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
                <input type="hidden" name="estudiante_ids[]" value="<?= $eid ?>">

                <td>
                  <select name="tipo[<?= $eid ?>]" required>
                    <option value="">-- seleccionar --</option>
                    <option value="presente"   <?= $asisHoy==='presente'    ? 'selected' : '' ?>>Presente</option>
                    <option value="ausente"    <?= $asisHoy==='ausente'     ? 'selected' : '' ?>>Ausente</option>
                    <option value="justificado"<?= $asisHoy==='justificado' ? 'selected' : '' ?>>Justificado</option>
                  </select>
                </td>
                <td><input type="text" name="obs[<?= $eid ?>]" value="<?= htmlspecialchars($obsHoy ?? '') ?>" placeholder="Obs."></td>
                <td>
                  <?php if ($asisHoy): ?>
                    <span class="badge <?= htmlspecialchars($asisHoy) ?>"><?= ucfirst($asisHoy) ?></span>
                  <?php else: ?>
                    <span class="muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button class="btn link" type="button"
                          data-est="<?= $eid ?>"
                          data-nom="<?= htmlspecialchars($e['nombre'] ?? '', ENT_QUOTES) ?>"
                          onclick="openFalta(this)">Falta…</button>
                </td>
                <td><button class="btn primary" type="submit">Guardar</button></td>
              </form>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($faltasDia)): ?>
    <div class="mt-10">
      <strong>Faltas registradas en <?= htmlspecialchars($fecha) ?>:</strong>
      <ul class="muted" style="margin:.35rem 0 .5rem 1rem">
        <?php foreach ($faltasDia as $f): ?>
          <li>#<?= (int)$f['id'] ?> — Est. <?= (int)$f['estudiante_id'] ?> ·
            <?= htmlspecialchars($f['tipo'] ?? ('Tipo '.$f['tipo_id'])) ?> ·
            <?= htmlspecialchars($f['descripcion'] ?? '') ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

</div>

<!-- Modal Falta -->
<div class="modal-backdrop" id="backdrop">
  <div class="modal">
    <header>Registrar falta</header>
    <form method="post" action="index.php?action=faltas_store" id="formFalta">
      <input type="hidden" name="clase_id" value="<?= (int)$clase['id'] ?>">
      <input type="hidden" name="estudiante_id" id="falta_estudiante_id">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
        <label>Fecha<br><input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>"></label>
        <label>Tipo de falta<br>
          <select name="tipo_id" required>
            <?php foreach ($tiposFalta as $tid=>$tnom): ?>
              <option value="<?= (int)$tid ?>"><?= htmlspecialchars($tnom) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <label style="display:block;margin-top:.5rem">Descripción<br>
        <textarea name="descripcion" rows="3" style="width:100%"></textarea>
      </label>
      <label style="display:flex;gap:.5rem;align-items:center;margin-top:.5rem">
        <input type="checkbox" name="enviar_correo" value="1"> Notificar por correo
      </label>
      <footer>
        <button type="button" class="btn" onclick="closeFalta()">Cancelar</button>
        <button type="submit" class="btn primary">Guardar</button>
      </footer>
    </form>
  </div>
</div>

<script>
  const backdrop = document.getElementById('backdrop');
  function openFalta(btn){
    document.getElementById('falta_estudiante_id').value = btn.dataset.est;
    backdrop.style.display = 'flex';
  }
  function closeFalta(){
    backdrop.style.display = 'none';
    const form = document.getElementById('formFalta');
    if (form) form.reset();
  }
  backdrop.addEventListener('click', (e)=>{ if(e.target===backdrop) closeFalta(); });
</script>
</body>
</html>
