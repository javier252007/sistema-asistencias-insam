<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Asistencias</title>
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<div class="container">
  <h1>Pase de lista (hoy)</h1>

  <?php if (empty($estudiantes)): ?>
    <p class="subtext">Esta clase no tiene estudiantes asignados.</p>
  <?php else: ?>
    <form method="post" action="index.php?action=asistencias_store">
      <input type="hidden" name="clase_id" value="<?= (int)($claseIdView ?? 0) ?>">
      <input type="hidden" name="fecha" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">

      <div style="margin:.5rem 0 1rem 0">
        <label style="margin-right:10px">
          <input type="radio" name="tipo_global" value="presente"> Marcar todos: Presente
        </label>
        <label style="margin-right:10px">
          <input type="radio" name="tipo_global" value="ausente"> Ausente
        </label>
        <label>
          <input type="radio" name="tipo_global" value="justificado"> Justificado
        </label>
      </div>

      <div class="cards-wrap">
        <?php foreach ($estudiantes as $al):
          $eid    = (int)($al['id'] ?? 0);
          $estado = $asisHoy[$eid] ?? '';
          // NIE puede venir como 'nie' (alias desde el controlador) o como 'NIE' (tal cual en la tabla)
          $nieRaw = $al['nie'] ?? ($al['NIE'] ?? '');
          $nombreRaw = $al['nombre'] ?? '';
        ?>
          <div class="card">
            <div class="card-icon">
              <?= $estado === 'presente' ? '✅' : ($estado === 'ausente' ? '❌' : '🕒') ?>
            </div>

            <h3>
              <?= htmlspecialchars($nombreRaw ?? '', ENT_QUOTES, 'UTF-8') ?>
            </h3>

            <p>
              NIE:
              <strong><?= htmlspecialchars($nieRaw ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
            </p>

            <div style="margin-top:.5rem">
              <label>
                <input type="radio" name="tipo[<?= $eid ?>]" value="presente" <?= $estado === 'presente' ? 'checked' : '' ?>> Presente
              </label>
              <label style="margin-left:10px">
                <input type="radio" name="tipo[<?= $eid ?>]" value="ausente" <?= $estado === 'ausente' ? 'checked' : '' ?>> Ausente
              </label>
              <label style="margin-left:10px">
                <input type="radio" name="tipo[<?= $eid ?>]" value="justificado" <?= $estado === 'justificado' ? 'checked' : '' ?>> Justificado
              </label>
            </div>

            <div style="margin-top:.4rem">
              <input type="hidden" name="estudiante_ids[]" value="<?= $eid ?>">
              <input
                type="text"
                name="obs[<?= $eid ?>]"
                placeholder="Observación (opcional)"
                value="<?= htmlspecialchars($obs_fila[$eid] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                style="width:100%;padding:.4rem"
              >
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <button class="btn-primary" type="submit" style="margin-top:1rem">Guardar pase</button>
    </form>
  <?php endif; ?>

  <p style="margin-top:1rem">
    <a class="btn small" href="index.php?action=docente_clases">⬅ Volver</a>
  </p>
</div>
</body>
</html>