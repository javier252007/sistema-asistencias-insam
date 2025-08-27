<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Usuarios</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/grupos.css"><!-- reutilizamos estilos de Grupos -->
</head>
<body>
  <div class="container">
    <h1>Usuarios</h1>

    <?php if (!empty($_SESSION['error'])): ?>
      <div class="error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
      <div class="success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <!-- REEMPLAZO -->
    <div class="search" style="justify-content:space-between; margin:12px 0;">
      <div>
        <a class="btn primary" href="index.php?action=usuarios_create">+ Nuevo usuario</a>
        <a class="btn link" href="index.php?action=dashboard">Volver</a>
      </div>
      <div>
        <input id="usuariosSearch" class="search-input" type="text"
               placeholder="Buscar por persona, usuario, rol...">
        <button id="btnBuscar" type="button" class="btn ghost">Buscar</button>
      </div>
    </div>
    <!-- FIN reemplazo -->

    <div class="table-wrap">
      <table class="table" id="tablaUsuarios"><!-- <- id necesario para el buscador -->
        <thead>
          <tr>
            <th>ID</th>
            <th>Persona</th>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Creado</th>
            <th style="width:120px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= htmlspecialchars($u['persona'] ?? '') ?></td>
              <td><?= htmlspecialchars($u['usuario'] ?? '') ?></td>
              <td><span class="badge"><?= htmlspecialchars($u['rol'] ?? '') ?></span></td>
              <td><?= htmlspecialchars($u['creado_en'] ?? '') ?></td>
              <td class="actions" style="text-align:right;">
                <a class="btn link" href="index.php?action=usuarios_edit&id=<?= (int)$u['id'] ?>">Editar</a>
                <form method="post" action="index.php?action=usuarios_destroy" onsubmit="return confirm('¿Eliminar usuario?');" style="display:inline;">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button class="btn danger small" type="submit">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($usuarios)): ?>
            <tr><td class="empty" colspan="6">No hay usuarios registrados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Activa el buscador -->
  <script src="js/usuarios.js"></script>
</body>
</html>
