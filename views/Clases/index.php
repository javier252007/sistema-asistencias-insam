<?php
// views/Clases/index.php
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Clases</title>
<link rel="stylesheet" href="css/dashboard.css">
<style>
.table { width:100%; border-collapse: collapse; }
.table th, .table td { border:1px solid #ddd; padding:8px; }
.actions { display:flex; gap:8px; }
.flash{background:#e7f7e8;padding:8px;border-radius:6px;margin:8px 0;color:#105c1d}
</style>
</head>
<body>
<div class="container">
  <h1>Clases</h1>

  <?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="flash"><?= htmlspecialchars($_SESSION['flash_msg']); unset($_SESSION['flash_msg']); ?></div>
  <?php endif; ?>

  <form method="get" action="index.php">
    <input type="hidden" name="action" value="clases_index">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por docente, grupo, asignatura, día...">
    <button type="submit">Buscar</button>
    <a href="index.php?action=clases_create">Nueva clase</a>
  </form>

  <table class="table" style="margin-top:10px;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Docente</th>
        <th>Grupo</th>
        <th>Asignatura</th>
        <th>Día</th>
        <th>Hora</th>
        <th>Aula</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['docente']) ?></td>
        <td><?= htmlspecialchars($r['grupo']) ?></td>
        <td><?= htmlspecialchars($r['asignatura'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['dia']) ?></td>
        <td><?= htmlspecialchars($r['hora_inicio'].' - '.$r['hora_fin']) ?></td>
        <td><?= htmlspecialchars($r['aula'] ?? '') ?></td>
        <td class="actions">
          <a href="index.php?action=clases_edit&id=<?= (int)$r['id'] ?>">Editar</a>
          <form method="post" action="index.php?action=clases_destroy" onsubmit="return confirm('¿Eliminar clase?');" style="display:inline">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button type="submit">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($pages>1): ?>
    <div style="margin-top:10px;">
      <?php for ($i=1;$i<=$pages;$i++): ?>
        <?php if ($i==$page): ?>
          <strong>[<?= $i ?>]</strong>
        <?php else: ?>
          <a href="index.php?action=clases_index&page=<?= $i ?>&q=<?= urlencode($q) ?>">[<?= $i ?>]</a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <p><a href="index.php?action=dashboard">Volver</a></p>
</div>
</body>
</html>
