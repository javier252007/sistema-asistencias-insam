<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar usuario</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/usuarios/usuarios.css">
</head>
<body>
  <div class="container">
    <h1>Editar usuario</h1>

    <?php if (!empty($_SESSION['error'])): ?>
      <div class="error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <form method="post" action="index.php?action=usuarios_update">
          <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">

          <div class="form-grid">
            <div class="form-field">
              <label>Persona</label>
              <input type="text" value="<?= htmlspecialchars($usuario['persona'] ?? '') ?>" readonly>
            </div>

            <div class="form-field">
              <label>Usuario</label>
              <input type="text" name="usuario" minlength="3" required value="<?= htmlspecialchars($usuario['usuario'] ?? '') ?>">
            </div>

            <div class="form-field">
              <label>Nueva contraseña (opcional)</label>
              <input type="password" name="contrasena" placeholder="Déjalo en blanco para no cambiar">
            </div>

            <div class="form-field">
              <label>Rol</label>
              <select name="rol" required>
                <option value="">-- Selecciona rol --</option>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= htmlspecialchars($r) ?>" <?= ($usuario['rol'] === $r ? 'selected' : '') ?>>
                    <?= htmlspecialchars(ucfirst($r)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-actions">
            <button class="btn primary" type="submit">Guardar cambios</button>
            <a class="btn ghost" href="index.php?action=usuarios_index">Cancelar</a>
          </div>
        </form>
      </div>
    </div>

    <p class="mt-10">
      <a class="btn link" href="index.php?action=usuarios_index">Volver</a>
    </p>
  </div>
</body>
</html>
