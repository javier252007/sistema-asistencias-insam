<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Mi Perfil</title>
<link rel="stylesheet" href="css/dashboard.css"></head>
<body>
<div class="container">
  <h1>Mi Perfil</h1>
  <?php if (empty($user)): ?>
    <p>No se encontró el usuario.</p>
  <?php else: ?>
    <p><strong>Usuario:</strong> <?= htmlspecialchars($user['usuario'] ?? '') ?></p>
    <p><strong>Nombre:</strong> <?= htmlspecialchars($user['nombre'] ?? '') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '') ?></p>
  <?php endif; ?>
  <p style="margin-top:1rem"><a class="btn small" href="index.php?action=dashboard">⬅ Volver</a></p>
</div>
</body></html>
