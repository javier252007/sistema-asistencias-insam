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
  tr.clickable:hover { background:#f5f5f5; cursor:pointer; }
  .actions a, .actions form { display:inline-block; margin-right:6px; }
</style>
</head>
<body>
<div class="container">
  <h1>Clases</h1>

  <?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="flash"><?= htmlspecialchars($_SESSION['flash_msg']); unset($_SESSION['flash_msg']); ?></div>
  <?php endif; ?>

  <p>
    <a href="index.php?action=clases_new">Nueva clase</a>
  </p>

  <form method="get" action="index.php">
    <input type="hidden" name="action" value="clases_index">
    <input type="text" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    <button type="submit">Buscar</button>
  </form>

  <table border="1" cellpadding="6" cellspacing="0" style="margin-top:10px; width:100%;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Docente</th>
        <th>Grupo</th>
        <th>Asignatura</th>
        <th>Día</th>
        <th>Período</th>
        <th>Hora</th>
        <th>Aula</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="9" style="text-align:center">Sin resultados</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $c): ?>
        <tr class="clickable" onclick="window.location='index.php?action=clases_show&id=<?= (int)$c['id'] ?>'">
          <td><?= (int)$c['id'] ?></td>
          <td><?= htmlspecialchars($c['docente']) ?></td>
          <td><?= htmlspecialchars($c['grupo']) ?></td>
          <td><?= htmlspecialchars($c['asignatura'] ?? '') ?></td>
          <td><?= htmlspecialchars($c['dia']) ?></td>
          <td><?= (int)$c['numero_periodo'] ?></td>
          <td><?= htmlspecialchars(substr($c['h_ini'],0,5)) ?>–<?= htmlspecialchars(substr($c['h_fin'],0,5)) ?></td>
          <td><?= htmlspecialchars($c['aula'] ?? '') ?></td>

          <!-- IMPORTANTE: detener propagación dentro de la celda de acciones -->
          <td class="actions" onclick="event.stopPropagation();">
            <a href="index.php?action=clases_show&id=<?= (int)$c['id'] ?>"
               onclick="event.stopPropagation();">Ver</a>

            <a href="index.php?action=clases_edit&id=<?= (int)$c['id'] ?>"
               onclick="event.stopPropagation();">Editar</a>

            <form method="post"
                  action="index.php?action=clases_destroy"
                  style="display:inline"
                  onsubmit="event.stopPropagation(); return confirm('¿Eliminar clase?');">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button type="submit" onclick="event.stopPropagation();">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>

  <?php if (isset($pages) && $pages>1): ?>
    <div class="pagination" style="margin-top:10px;">
      <?php
        $q = urlencode($_GET['q'] ?? '');
        for ($i=1;$i<=$pages;$i++):
      ?>
        <a href="index.php?action=clases_index&page=<?= $i ?>&q=<?= $q ?>" <?= ((int)($_GET['page'] ?? 1)===$i)?'style="font-weight:bold"':'' ?>><?= $i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<script>
// Blindaje extra por si algún navegador ignora el inline:
document.querySelectorAll('td.actions, td.actions *').forEach(el => {
  el.addEventListener('click', e => e.stopPropagation());
});
</script>
</body>
</html>
