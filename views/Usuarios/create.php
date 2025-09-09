<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo usuario</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/usuarios/usuarios.css">
</head>
<body>
  <div class="container">
    <h1>Crear usuario</h1>

    <?php if (!empty($_SESSION['error'])): ?>
      <div class="error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="index.php?action=usuarios_store">
      <div class="form-grid">
        <div class="form-field">
          <label>Persona</label>
          <select name="persona_id" required>
            <option value="">-- Selecciona persona --</option>
            <?php foreach ($personas as $p): ?>
              <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-field">
          <label>Usuario</label>
          <input type="text" name="usuario" minlength="3" required placeholder="ej. jperez">
        </div>

        <div class="form-field">
          <label>Contraseña</label>
          <input type="password" name="contrasena" minlength="4" required>
        </div>

        <div class="form-field">
          <label>Rol</label>
          <select name="rol" required>
            <option value="">-- Selecciona rol --</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars(ucfirst($r)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="full form-actions">
          <button class="btn primary" type="submit">💾 Guardar</button>
          <a class="btn secondary" href="index.php?action=usuarios_index">Cancelar</a>
        </div>
      </div>
    </form>
  </div>
</body>
</html>
