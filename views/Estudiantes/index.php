<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Estudiantes — Listado</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/estudiantes/estudiantes.css">
</head>
<body>
  <div class="container">

    <div class="toolbar">
      <a class="btn icon" href="index.php?action=estudiantes_create" title="Nuevo estudiante">＋</a>

      <form class="search" method="get" action="index.php">
        <input type="hidden" name="action" value="estudiantes_index">
        <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>"
               placeholder="Buscar por nombre, NIE o grupo">
        <button class="btn" type="submit">Buscar</button>
      </form>
    </div>

    <h1>Estudiantes</h1>

    <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="alert <?= htmlspecialchars($f['type']) ?>">
        <?php foreach ($f['messages'] as $m): ?>
          <p><?= htmlspecialchars($m) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>NIE</th>
            <th>Nombre</th>
            <th>Fecha nac.</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Grupo</th>
            <th>Estado</th>
            <th>Editar</th>
            <th>Eliminar</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($rows ?? []) as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['NIE'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['nombre'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['fecha_nacimiento'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['telefono'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['correo'] ?? '') ?></td>

              <td>
                <?php
                  $grupoTxt = trim((string)($r['grado'] ?? '') . ' ' . (string)($r['seccion'] ?? ''));
                  echo $grupoTxt !== ''
                    ? htmlspecialchars($grupoTxt)
                    : '<span class="badge badge-warning">Sin grupo</span>';
                ?>
              </td>

              <td>
                <span class="badge <?= ($r['estado'] ?? 'activo') === 'activo' ? 'ok' : 'off' ?>">
                  <?= htmlspecialchars($r['estado'] ?? 'activo') ?>
                </span>
              </td>

              <td>
                <a class="btn small" href="index.php?action=estudiantes_edit&id=<?= (int)$r['id'] ?>">Editar</a>
              </td>
              <td>
                <form method="post"
                      action="index.php?action=estudiantes_destroy"
                      onsubmit="return confirm('Esta acción eliminará PERMANENTEMENTE al estudiante y sus registros relacionados. ¿Continuar?');"
                      class="inline">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn danger small" type="submit">Eliminar</button>
                </form>
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
      <a class="page <?= $page <= 1 ? 'disabled' : '' ?>"
         href="<?= $page <= 1 ? '#' : $base . '&page=' . ($page-1) ?>">« Anterior</a>
      <span class="info">Página <?= (int)$page ?> de <?= (int)$pages ?></span>
      <a class="page <?= $page >= $pages ? 'disabled' : '' ?>"
         href="<?= $page >= $pages ? '#' : $base . '&page=' . ($page+1) ?>">Siguiente »</a>
    </div>

    <div class="mt-10">
      <a class="btn secondary" href="index.php?action=dashboard">Volver al dashboard</a>
    </div>
  </div>
</body>
</html>
