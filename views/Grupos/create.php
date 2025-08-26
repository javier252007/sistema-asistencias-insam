<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Nuevo grupo</title>
  <link rel="stylesheet" href="css/dashboard.css" />
  <link rel="stylesheet" href="css/grupos.css" />
</head>
<body>
  <div class="container">
    <div class="page-header">
      <h1>Nuevo grupo</h1>
      <p class="muted">Define año lectivo, grado, sección, modalidad y docente guía.</p>
    </div>

    <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="alert <?= htmlspecialchars($f['type']) ?>">
        <?php foreach ($f['messages'] as $m): ?>
          <p><?= htmlspecialchars($m) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="index.php?action=grupos_create" class="card">
      <div class="card-body">
        <div class="form-grid">
          <div class="form-field">
            <label for="anio_lectivo">Año lectivo</label>
            <input type="number" id="anio_lectivo" name="anio_lectivo" required min="2000" max="2100" value="<?= date('Y') ?>" />
          </div>

          <div class="form-field">
            <label for="grado">Grado</label>
            <input type="text" id="grado" name="grado" required placeholder="Ej: 1.º Básico" />
          </div>

          <div class="form-field">
            <label for="seccion">Sección</label>
            <input type="text" id="seccion" name="seccion" required placeholder="Ej: A" />
          </div>

          <div class="form-field">
            <label for="modalidad_id">Modalidad</label>
            <select id="modalidad_id" name="modalidad_id">
              <option value="">— Seleccionar —</option>
              <?php foreach ($modalidades as $m): ?>
                <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Ocupa el ancho completo -->
          <div class="form-field span-2">
            <label for="docente_guia_id">Docente guía</label>
            <select id="docente_guia_id" name="docente_guia_id">
              <option value="">— Seleccionar —</option>
              <?php foreach ($docentes as $d): ?>
                <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn primary" type="submit">Crear</button>
          <a class="btn ghost" href="index.php?action=grupos_index">Cancelar</a>
        </div>
      </div>
    </form>
  </div>
</body>
</html>
