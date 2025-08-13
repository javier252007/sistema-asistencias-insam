<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Estudiantes — Listado</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <div class="container">
    <div class="toolbar">
      <!-- Botón + para registrar -->
      <a class="btn icon" href="index.php?action=estudiantes_create" title="Nuevo estudiante">＋</a>

      <!-- Buscador -->
      <form class="search" method="get" action="index.php">
        <input type="hidden" name="action" value="estudiantes_index">
        <input type="text" name="q" placeholder="Buscar por nombre o NIE" value="<?= htmlspecialchars($q ?? '') ?>">
        <button class="btn" type="submit">Buscar</button>
      </form>
    </div>

    <h1>Estudiantes</h1>

    <?php if (!empty($flash)): ?>
      <?php if ($flash['type'] === 'error'): ?>
        <div class="alert error">
          <ul>
            <?php foreach ($flash['messages'] as $m): ?>
              <li><?= htmlspecialchars($m) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php else: ?>
        <div class="alert success">
          <?php foreach ($flash['messages'] as $m): ?>
            <div><?= htmlspecialchars($m) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (($total ?? 0) === 0): ?>
      <p>No hay estudiantes registrados.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>NIE</th>
              <th>Nombre</th>
              <th>Fecha nac.</th>
              <th>Teléfono</th>
              <th>Correo</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (($rows ?? []) as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['NIE']) ?></td>
                <td><?= htmlspecialchars($r['nombre']) ?></td>
                <td><?= htmlspecialchars($r['fecha_nacimiento'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['telefono'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['correo'] ?? '') ?></td>
                <td>
                  <span class="badge <?= ($r['estado']==='activo') ? 'ok' : 'off' ?>">
                    <?= htmlspecialchars($r['estado']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php
        $page  = $page ?? 1;
        $pages = $pages ?? 1;
        $q     = $q ?? '';
        $base  = 'index.php?action=estudiantes_index';
        if ($q !== '') $base .= '&q=' . urlencode($q);
      ?>
      <div class="pagination">
        <a class="page <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $page <= 1 ? '#' : $base . '&page=' . ($page-1) ?>">« Anterior</a>
        <span class="info">Página <?= (int)$page ?> de <?= (int)$pages ?></span>
        <a class="page <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= $page >= $pages ? '#' : $base . '&page=' . ($page+1) ?>">Siguiente »</a>
      </div>
    <?php endif; ?>

    <div style="margin-top:1rem;">
      <a class="btn secondary" href="index.php?action=dashboard">Volver al dashboard</a>
    </div>
  </div>
</body>
</html>
