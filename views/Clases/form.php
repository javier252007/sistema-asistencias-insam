<?php
// views/Clases/form.php
// Versión robusta: no asume que siempre exista ['nombre'] y evita null en htmlspecialchars.

$DIAS = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$horarios = isset($horarios) && is_array($horarios) ? $horarios : [];

// Helper seguro para imprimir
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Arma etiquetas de manera flexible según lo que venga del modelo/SQL
function labelDocente(array $d): string {
  // prioridad: nombre | persona | full_name | docente | fallback
  $candidatos = [
    $d['nombre']   ?? null,
    $d['persona']  ?? null,
    $d['full_name']?? null,
    $d['docente']  ?? null,
  ];
  foreach ($candidatos as $c) if (!empty($c)) return (string)$c;
  return 'Docente #'.(($d['id'] ?? '') ?: '?');
}
function labelGrupo(array $g): string {
  // si ya viene 'nombre', úsalo; si no, compón "grado seccion (anio)"
  if (!empty($g['nombre'])) return (string)$g['nombre'];
  $grado  = $g['grado']  ?? '';
  $secc   = $g['seccion']?? '';
  $anio   = $g['anio_lectivo'] ?? ($g['anio'] ?? '');
  $base   = trim($grado.' '.$secc);
  if ($base === '' && !empty($g['descripcion'])) $base = (string)$g['descripcion'];
  if ($anio !== '') $base .= ' ('.$anio.')';
  return $base !== '' ? $base : 'Grupo #'.(($g['id'] ?? '') ?: '?');
}
function labelAsignatura(array $a): string {
  $candidatos = [
    $a['nombre'] ?? null,
    $a['asignatura'] ?? null,
    $a['titulo'] ?? null,
  ];
  foreach ($candidatos as $c) if (!empty($c)) return (string)$c;
  return 'Asignatura #'.(($a['id'] ?? '') ?: '?');
}

// Selección de horario en edición (por id o por horas)
$selectedHorarioId = $clase['horario_id'] ?? null;
if (!$selectedHorarioId && !empty($clase['hora_inicio']) && !empty($clase['hora_fin']) && !empty($horarios)) {
  $hiEdit = substr((string)$clase['hora_inicio'], 0, 5);
  $hfEdit = substr((string)$clase['hora_fin'], 0, 5);
  foreach ($horarios as $hrow) {
    if (substr((string)$hrow['hora_inicio'], 0, 5) === $hiEdit && substr((string)$hrow['hora_fin'], 0, 5) === $hfEdit) {
      $selectedHorarioId = (int)$hrow['id'];
      break;
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= !empty($isEdit) ? 'Editar clase' : 'Nueva clase' ?></title>
<link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<div class="container">
  <h1><?= !empty($isEdit) ? 'Editar clase' : 'Nueva clase' ?></h1>

  <?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="flash"><?= h($_SESSION['flash_msg']); unset($_SESSION['flash_msg']); ?></div>
  <?php endif; ?>

  <form method="post" action="index.php?action=<?= !empty($isEdit) ? 'clases_update' : 'clases_create' ?>">
    <?php if (!empty($isEdit)): ?>
      <input type="hidden" name="id" value="<?= (int)($clase['id'] ?? 0) ?>">
    <?php endif; ?>

    <!-- Docente -->
    <div>
      <label>Docente</label>
      <select name="docente_id" id="docente_id" required>
        <option value="">Seleccione...</option>
        <?php foreach (($docentes ?? []) as $d): ?>
          <?php
            $id  = (int)($d['id'] ?? 0);
            $lbl = labelDocente($d);
            $sel = (!empty($isEdit) && (int)($clase['docente_id'] ?? 0) === $id) ? 'selected' : '';
          ?>
          <option value="<?= $id ?>" <?= $sel ?>><?= h($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Grupo -->
    <div>
      <label>Grupo</label>
      <select name="grupo_id" id="grupo_id" required>
        <option value="">Seleccione...</option>
        <?php foreach (($grupos ?? []) as $g): ?>
          <?php
            $id  = (int)($g['id'] ?? 0);
            $lbl = labelGrupo($g);
            $sel = (!empty($isEdit) && (int)($clase['grupo_id'] ?? 0) === $id) ? 'selected' : '';
          ?>
          <option value="<?= $id ?>" <?= $sel ?>><?= h($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Asignatura -->
    <div>
      <label>Asignatura</label>
      <select name="asignatura_id" id="asignatura_id">
        <option value="">(sin asignatura)</option>
        <?php foreach (($asignaturas ?? []) as $a): ?>
          <?php
            $id  = (int)($a['id'] ?? 0);
            $lbl = labelAsignatura($a);
            $sel = (!empty($isEdit) && (int)($clase['asignatura_id'] ?? 0) === $id) ? 'selected' : '';
          ?>
          <option value="<?= $id ?>" <?= $sel ?>><?= h($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Día -->
    <div>
      <label>Día</label>
      <select name="dia" id="dia" required>
        <option value="">Seleccione...</option>
        <?php foreach ($DIAS as $d): ?>
          <option value="<?= h($d) ?>" <?= (!empty($isEdit) && ($clase['dia'] ?? '') === $d) ? 'selected' : '' ?>><?= h($d) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Período -->
    <div>
      <label>Período (número)</label>
      <select name="horario_id" id="horario_id" required>
        <option value="">Seleccione...</option>
        <?php foreach ($horarios as $hrow): ?>
          <?php
            $hid = (int)($hrow['id'] ?? 0);
            $hi  = substr((string)($hrow['hora_inicio'] ?? ''), 0, 5);
            $hf  = substr((string)($hrow['hora_fin']    ?? ''), 0, 5);
            $num = (int)($hrow['numero_periodo'] ?? 0);
            $sel = ($selectedHorarioId !== null && (int)$selectedHorarioId === $hid) ? 'selected' : '';
          ?>
          <option value="<?= $hid ?>" data-inicio="<?= h($hi) ?>" data-fin="<?= h($hf) ?>" <?= $sel ?>>
            <?= $num ?> (<?= h($hi) ?>–<?= h($hf) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (empty($horarios)): ?>
        <div style="color:#b00;margin-top:4px;">No hay períodos cargados. Crea la tabla <code>horarios</code> y agrega filas.</div>
      <?php endif; ?>
    </div>

    <!-- Horas (solo lectura; se rellenan automáticamente al elegir período) -->
    <div>
      <label>Hora inicio</label>
      <input type="time" name="hora_inicio" id="hora_inicio" value="<?= h($clase['hora_inicio'] ?? '') ?>" readonly required>
    </div>

    <div>
      <label>Hora fin</label>
      <input type="time" name="hora_fin" id="hora_fin" value="<?= h($clase['hora_fin'] ?? '') ?>" readonly required>
    </div>

    <div>
      <label>Aula</label>
      <input type="text" name="aula" id="aula" value="<?= h($clase['aula'] ?? '') ?>">
    </div>

    <div style="margin-top:10px;">
      <button type="submit"><?= !empty($isEdit) ? 'Actualizar' : 'Crear' ?></button>
      <a href="index.php?action=clases_index">Cancelar</a>
    </div>
  </form>
</div>

<script>
// Autorrellena horas según el período
(function () {
  var sel = document.getElementById('horario_id');
  var hi  = document.getElementById('hora_inicio');
  var hf  = document.getElementById('hora_fin');
  function applyFromSelected() {
    var opt = sel && sel.options[sel.selectedIndex];
    if (!opt) return;
    hi.value = opt.getAttribute('data-inicio') || '';
    hf.value = opt.getAttribute('data-fin') || '';
  }
  if (sel) {
    sel.addEventListener('change', applyFromSelected);
    if (sel.value && (!hi.value || !hf.value)) applyFromSelected();
  }
})();
</script>
</body>
</html>
