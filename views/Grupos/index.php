<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Grupos</title>
  <link rel="stylesheet" href="css/dashboard.css" />
  <link rel="stylesheet" href="css/grupos.css" />
</head>
<body>
  <div class="container">
    <div class="page-header">
      <h1>Grupos</h1>
      <p class="muted">Gestiona los grupos: año, grado, sección, modalidad y docente guía.</p>
    </div>

    <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="alert <?= htmlspecialchars($f['type']) ?>">
        <?php foreach ($f['messages'] as $m): ?>
          <p><?= htmlspecialchars($m) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="card card-list">
      <div class="card-body">

        <!-- Toolbar -->
        <div class="toolbar">
          <div class="left">
            <a class="btn primary" href="index.php?action=grupos_create">+ Nuevo grupo</a>
          </div>
          <div class="right">
            <form class="search" method="get" action="index.php">
              <input type="hidden" name="action" value="grupos_index">
              <input class="search-input" type="search" name="q"
                     placeholder="Buscar por grado, sección, modalidad..."
                     value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
              <button class="btn ghost" type="submit">Buscar</button>
            </form>
          </div>
        </div>

        <!-- Tabla -->
        <div class="table-wrap">
          <table class="table vlines">
            <thead>
              <tr>
                <th>Año</th>
                <th>Grado</th>
                <th>Sección</th>
                <th>Modalidad</th>
                <th>Docente guía</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($grupos)): ?>
                <tr>
                  <td colspan="6" class="empty">
                    No hay grupos registrados.
                    <a href="index.php?action=grupos_create" class="link">Crear el primero</a>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($grupos as $g): ?>
                  <tr>
                    <td><span class="badge"><?= htmlspecialchars($g['anio_lectivo']) ?></span></td>
                    <td><?= htmlspecialchars($g['grado']) ?></td>
                    <td><?= htmlspecialchars($g['seccion']) ?></td>
                    <td><?= htmlspecialchars($g['modalidad_nombre'] ?? $g['modalidad'] ?? '') ?></td>
                    <td><?= htmlspecialchars($g['docente_nombre'] ?? $g['docente'] ?? '') ?></td>
                    <td class="actions">
                      <a class="btn link" href="index.php?action=grupos_edit&id=<?= (int)$g['id'] ?>">Editar</a>
                      <form method="post" action="index.php?action=grupos_destroy"
                            onsubmit="return confirm('¿Eliminar este grupo?');">
                        <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                        <button class="btn danger" type="submit">Eliminar</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <?php
          $page   = max(1, (int)($_GET['page'] ?? 1));
          $qParam = isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '';
          $prevUrl = "index.php?action=grupos_index&page=" . max(1, $page - 1) . $qParam;
          $nextUrl = "index.php?action=grupos_index&page=" . ($page + 1) . $qParam;
        ?>
        <div class="pagination">
          <a class="page-link" href="<?= $prevUrl ?>">« Anterior</a>
          <span class="page-info">Página <?= $page ?></span>
          <a class="page-link" href="<?= $nextUrl ?>">Siguiente »</a>
        </div>

        <div class="mt-16">
          <a class="link" href="index.php?action=dashboard">Volver al dashboard</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
