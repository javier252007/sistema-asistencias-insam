<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar grupo</title>
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
  <div class="container">
    <h1>Editar grupo</h1>

    <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="alert <?= htmlspecialchars($f['type']) ?>">
        <?php foreach ($f['messages'] as $m): ?><p><?= htmlspecialchars($m) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$g): ?>
      <p>No se encontró el grupo.</p>
      <a class="btn" href="index.php?action=grupos_index">Volver</a>
    <?php else: ?>
      <form method="post" action="index.php?action=grupos_update">
        <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">

        <div class="form-card">
          <div class="form-grid">
            <div>
              <label for="anio_lectivo">Año lectivo</label>
              <input type="number" id="anio_lectivo" name="anio_lectivo" required min="2000" max="2100" value="<?= htmlspecialchars($g['anio_lectivo']) ?>">
            </div>
            <div>
              <label for="grado">Grado</label>
              <input type="text" id="grado" name="grado" required value="<?= htmlspecialchars($g['grado']) ?>">
            </div>
            <div>
              <label for="seccion">Sección</label>
              <input type="text" id="seccion" name="seccion" required value="<?= htmlspecialchars($g['seccion']) ?>">
            </div>
            <div>
              <label for="modalidad_id">Modalidad</label>
              <select id="modalidad_id" name="modalidad_id">
                <option value="">— Seleccionar —</option>
                <?php foreach ($modalidades as $m): ?>
                  <option value="<?= (int)$m['id'] ?>" <?= ((int)$m['id'] === (int)$g['modalidad_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="docente_guia_id">Docente guía</label>
              <select id="docente_guia_id" name="docente_guia_id">
                <option value="">— Seleccionar —</option>
                <?php foreach ($docentes as $d): ?>
                  <option value="<?= (int)$d['id'] ?>" <?= ((int)$d['id'] === (int)$g['docente_guia_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="actions">
            <button class="btn primary" type="submit">Guardar</button>
            <a class="btn secondary" href="index.php?action=grupos_index">Cancelar</a>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
