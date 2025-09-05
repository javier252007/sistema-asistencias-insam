<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar estudiante</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <div class="container">
    <h1>Editar estudiante</h1>

    <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="alert <?= htmlspecialchars($f['type']) ?>">
        <?php foreach ($f['messages'] as $m): ?><p><?= htmlspecialchars($m) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!isset($est) || !$est): ?>
      <p>No se encontró el estudiante.</p>
      <a class="btn" href="index.php?action=estudiantes_index">Volver</a>
    <?php else: ?>
      <form method="post" action="index.php?action=estudiantes_update">
        <input type="hidden" name="id" value="<?= (int)$est['id'] ?>">
        <input type="hidden" name="persona_id" value="<?= (int)$est['persona_id'] ?>">

        <div class="form-card">
          <div class="form-grid">
            <div>
              <label for="nombre">Nombre</label>
              <input type="text" id="nombre" name="nombre" required
                     value="<?= htmlspecialchars($est['nombre']) ?>">
            </div>

            <div>
              <label for="fecha_nacimiento">Fecha nac.</label>
              <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                     value="<?= htmlspecialchars($est['fecha_nacimiento'] ?? '') ?>">
            </div>

            <div>
              <label for="telefono">Teléfono</label>
              <input type="text" id="telefono" name="telefono"
                     value="<?= htmlspecialchars($est['telefono'] ?? '') ?>">
            </div>

            <div>
              <label for="correo">Correo</label>
              <input type="email" id="correo" name="correo"
                     value="<?= htmlspecialchars($est['correo'] ?? '') ?>">
            </div>

            <div class="full">
              <label for="direccion">Dirección</label>
              <textarea id="direccion" name="direccion" rows="2"><?= htmlspecialchars($est['direccion'] ?? '') ?></textarea>
            </div>

            <div>
              <label for="NIE">NIE</label>
              <input type="text" id="NIE" name="NIE" required
                     value="<?= htmlspecialchars($est['NIE']) ?>">
            </div>

            <div>
              <label for="estado">Estado</label>
              <select id="estado" name="estado">
                <option value="activo"   <?= ($est['estado'] === 'activo')   ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($est['estado'] === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
              </select>
            </div>

            <!-- NUEVO: selector de Grupo -->
            <div>
              <label for="grupo_id">Grupo*</label>
              <select id="grupo_id" name="grupo_id" required>
                <option value="">— Seleccione —</option>
                <?php
                  $sel = (int)($est['grupo_id'] ?? 0);
                  foreach ($grupos as $g):
                    $gid = (int)$g['id'];
                    $txt = trim(($g['grado'] ?? '').' - '.($g['seccion'] ?? ''));
                ?>
                  <option value="<?= $gid ?>" <?= $gid === $sel ? 'selected' : '' ?>>
                    <?= htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <!-- /NUEVO -->
          </div>

          <div class="actions">
            <button class="btn primary" type="submit">Guardar</button>
            <a class="btn secondary" href="index.php?action=estudiantes_index">Cancelar</a>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
