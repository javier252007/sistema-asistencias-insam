<!-- views/login.php -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login INSAM</title>
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <div class="card">
    <h2>Login</h2>
    <h3>Bienvenido</h3>
    <?php if (!empty(\$error)): ?>
      <div class="error"><?= htmlspecialchars(\$error) ?></div>
    <?php endif ?>
    <form id="loginForm" method="post" action="index.php?action=login">
      <input type="text" name="usuario" placeholder="Usuario" required>
      <input type="password" name="contrasena" placeholder="Contraseña" required>
      <button type="submit">Continuar</button>
    </form>
  </div>
  <script src="js/login.js"></script>
</body>
</html>