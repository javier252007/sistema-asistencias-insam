<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Estudiante</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/estudiantes/estudiantes.css">
</head>
<body>
  <?php
    // Asegurar $flash y limpiar después de mostrar
    $flash = $_SESSION['flash'] ?? null;
    if (isset($_SESSION['flash'])) unset($_SESSION['flash']);
  ?>
  <div class="container">
    <h1>Estudiantes — Registrar</h1>

    <?php if (!empty($flash)): ?>
      <?php if (($flash['type'] ?? '') === 'error'): ?>
        <div class="alert error">
          <ul>
            <?php foreach (($flash['messages'] ?? []) as $m): ?>
              <li><?= htmlspecialchars($m) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php else: ?>
        <div class="alert success">
          <?php foreach (($flash['messages'] ?? []) as $m): ?>
            <div><?= htmlspecialchars($m) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="form-card">
      <form method="post" action="index.php?action=estudiantes_store" enctype="multipart/form-data">
        <div class="form-grid">
          <div class="full">
            <label for="nombre">Nombre completo*</label>
            <input type="text" id="nombre" name="nombre" required
                   value="<?= htmlspecialchars($flash['old']['nombre'] ?? '') ?>">
          </div>

          <div>
            <label for="fecha_nacimiento">Fecha de nacimiento</label>
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                   value="<?= htmlspecialchars($flash['old']['fecha_nacimiento'] ?? '') ?>">
          </div>

          <div>
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono"
                   value="<?= htmlspecialchars($flash['old']['telefono'] ?? '') ?>">
          </div>

          <div>
            <label for="correo">Correo</label>
            <input type="email" id="correo" name="correo"
                   value="<?= htmlspecialchars($flash['old']['correo'] ?? '') ?>">
          </div>

          <div class="full">
            <label for="direccion">Dirección</label>
            <input type="text" id="direccion" name="direccion"
                   value="<?= htmlspecialchars($flash['old']['direccion'] ?? '') ?>">
          </div>

          <div>
            <label for="NIE">NIE*</label>
            <input type="text" id="NIE" name="NIE" required
                   value="<?= htmlspecialchars($flash['old']['NIE'] ?? '') ?>">
          </div>

          <div>
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
              <?php $est = $flash['old']['estado'] ?? 'activo'; ?>
              <option value="activo"   <?= $est==='activo'?'selected':''; ?>>Activo</option>
              <option value="inactivo" <?= $est==='inactivo'?'selected':''; ?>>Inactivo</option>
            </select>
          </div>

          <!-- Selector de Grupo -->
          <div>
            <label for="grupo_id">Grupo*</label>
            <select id="grupo_id" name="grupo_id" required>
              <option value="">— Seleccione —</option>
              <?php
                $oldGrupo = isset($flash['old']['grupo_id']) ? (int)$flash['old']['grupo_id'] : 0;
                foreach ($grupos as $g):
                  $gid = (int)$g['id'];
                  $txt = trim(($g['grado'] ?? '').' - '.($g['seccion'] ?? ''));
              ?>
                <option value="<?= $gid ?>" <?= $oldGrupo === $gid ? 'selected' : '' ?>>
                  <?= htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="full">
            <label for="foto">Foto (opcional)</label>
            <input type="file" id="foto" name="foto" accept="image/*">
          </div>
        </div>

        <div class="actions">
          <button class="btn primary" type="submit">Guardar</button>
          <a class="btn secondary" href="index.php?action=dashboard">Volver al dashboard</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
