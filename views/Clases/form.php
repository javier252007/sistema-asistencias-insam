<?php
// views/Clases/form.php

$DIAS = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$horarios = isset($horarios) && is_array($horarios) ? $horarios : [];

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function labelDocente(array $d): string {
  foreach ([$d['nombre']??null,$d['persona']??null,$d['full_name']??null,$d['docente']??null] as $c)
    if (!empty($c)) return (string)$c;
  return 'Docente #'.(($d['id'] ?? '') ?: '?');
}
function labelGrupo(array $g): string {
  if (!empty($g['nombre'])) return (string)$g['nombre'];
  $grado  = $g['grado']??''; $secc=$g['seccion']??''; $anio=$g['anio_lectivo']??($g['anio']??'');
  $base = trim($grado.' '.$secc);
  if ($base==='' && !empty($g['descripcion'])) $base=(string)$g['descripcion'];
  if ($anio!=='') $base.=" ($anio)";
  return $base!=='' ? $base : 'Grupo #'.(($g['id'] ?? '') ?: '?');
}
function labelAsignatura(array $a): string {
  foreach ([$a['nombre']??null,$a['asignatura']??null,$a['titulo']??null] as $c)
    if (!empty($c)) return (string)$c;
  return 'Asignatura #'.(($a['id'] ?? '') ?: '?');
}

$selectedHorarioId = $clase['horario_id'] ?? null;
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
      <select name="docente_id" required>
        <option value="">Seleccione...</option>
        <?php foreach (($docentes ?? []) as $d): ?>
          <?php $id=(int)($d['id']??0); $lbl=labelDocente($d);
          $sel=(!empty($isEdit)&&(int)($clase['docente_id']??0)===$id)?'selected':''; ?>
          <option value="<?= $id ?>" <?= $sel ?>><?= h($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Grupo -->
    <div>
      <label>Grupo</label>
      <select name="grupo_id" required>
        <option value="">Seleccione...</option>
        <?php foreach (($grupos ?? []) as $g): ?>
          <?php $id=(int)($g['id']??0); $lbl=labelGrupo($g);
          $sel=(!empty($isEdit)&&(int)($clase['grupo_id']??0)===$id)?'selected':''; ?>
          <option value="<?= $id ?>" <?= $sel ?>><?= h($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Asignatura -->
    <div>
      <label>Asignatura</label>
      <select name="asignatura_id">
        <option value="">(sin asignatura)</option>
        <?php foreach (($asignaturas ?? []) as $a): ?>
          <?php $id=(int)($a['id']??0); $lbl=labelAsignatura($a);
          $sel=(!empty($isEdit)&&(int)($clase['asignatura_id']??0)===$id)?'selected':''; ?>
          <option value="<?= $id ?>" <?= $sel ?>><?= h($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Día -->
    <div>
      <label>Día</label>
      <select name="dia" required>
        <option value="">Seleccione...</option>
        <?php foreach ($DIAS as $d): ?>
          <option value="<?= h($d) ?>" <?= (!empty($isEdit)&&($clase['dia']??'')===$d)?'selected':'' ?>><?= h($d) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Período -->
    <div>
      <label>Período (número)</label>
      <select name="horario_id" required>
        <option value="">Seleccione...</option>
        <?php foreach ($horarios as $hrow): ?>
          <?php $hid=(int)($hrow['id']??0);
          $hi=substr((string)($hrow['hora_inicio']??''),0,5);
          $hf=substr((string)($hrow['hora_fin']??''),0,5);
          $num=(int)($hrow['numero_periodo']??0);
          $sel=($selectedHorarioId!==null&&(int)$selectedHorarioId===$hid)?'selected':''; ?>
          <option value="<?= $hid ?>" <?= $sel ?>>
            <?= $num ?> (<?= h($hi) ?>–<?= h($hf) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (empty($horarios)): ?>
        <div style="color:#b00;margin-top:4px;">No hay períodos cargados. Crea la tabla <code>horarios</code> y agrega filas.</div>
      <?php endif; ?>
    </div>

    <!-- Aula -->
    <div>
      <label>Aula</label>
      <input type="text" name="aula" value="<?= h($clase['aula'] ?? '') ?>">
    </div>

    <div style="margin-top:10px;">
      <button type="submit"><?= !empty($isEdit)?'Actualizar':'Crear' ?></button>
      <a href="index.php?action=clases_index">Cancelar</a>
    </div>
  </form>
</div>
</body>
</html>
