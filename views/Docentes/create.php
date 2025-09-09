<?php /* views/Docentes/create.php */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Docente</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/docentes/docentes.css">
</head>
<body>
  <div class="container">
    <h1>Docentes — Registrar</h1>

    <?php if (!empty($flash)): ?>
      <?php if ($flash['type'] === 'error'): ?>
        <div class="alert error">
          <ul><?php foreach ($flash['messages'] as $m): ?><li><?= htmlspecialchars($m) ?></li><?php endforeach; ?></ul>
        </div>
      <?php else: ?>
        <div class="alert success">
          <?php foreach ($flash['messages'] as $m): ?><div><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="form-card">
      <form method="post" action="index.php?action=docentes_store">
        <div class="form-grid">
          <div class="full">
            <label for="nombre">Nombre completo*</label>
            <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($flash['old']['nombre'] ?? '') ?>">
          </div>
          <div>
            <label for="fecha_nacimiento">Fecha de nacimiento</label>
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= htmlspecialchars($flash['old']['fecha_nacimiento'] ?? '') ?>">
          </div>
          <div>
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($flash['old']['telefono'] ?? '') ?>">
          </div>
          <div>
            <label for="correo">Correo</label>
            <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($flash['old']['correo'] ?? '') ?>">
          </div>
          <div class="full">
            <label for="direccion">Dirección</label>
            <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($flash['old']['direccion'] ?? '') ?>">
          </div>
          <div>
            <label for="activo">Activo</label>
            <select id="activo" name="activo">
              <?php $act = $flash['old']['activo'] ?? '1'; ?>
              <option value="1" <?= $act==='1'?'selected':''; ?>>Sí</option>
              <option value="0" <?= $act==='0'?'selected':''; ?>>No</option>
            </select>
          </div>
        </div>
        <div class="actions">
          <button class="btn primary" type="submit">Guardar</button>
          <a class="btn secondary" href="index.php?action=docentes_index">Volver al listado</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
