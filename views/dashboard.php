<!-- views/dashboard.php -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard INSAM</title>
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
  <div class="container">
    <h1>Panel de Control</h1>
    <p>Bienvenido, tu rol es: <strong><?= htmlspecialchars($rol) ?></strong></p>

    <div class="cards-wrap">
      <?php
        $all = [
          'asistencia' => ['title'=>'Asistencias','desc'=>'Registrar y consultar asistencias','icon'=>'🗓️','enabled_roles'=>['admin','docente']],
          'usuarios'   => ['title'=>'Usuarios','desc'=>'Gestión de usuarios y roles','icon'=>'👥','enabled_roles'=>['admin']],
          'reportes'   => ['title'=>'Reportes','desc'=>'Estadísticas e informes','icon'=>'📈','enabled_roles'=>['admin','docente','orientador','directora']],
          // La tarjeta de Estudiantes debe llevar a la acción estudiantes_create
          'estudiantes_create'=> ['title'=>'Estudiantes','desc'=>'Registrar y gestionar estudiantes','icon'=>'🎓','enabled_roles'=>['admin']],
        ];

        foreach ($all as $action => $info):
          $enabled = in_array($rol, $info['enabled_roles'], true);
          $cls     = 'card' . ($enabled ? '' : ' disabled');
          $href    = $enabled ? "index.php?action={$action}" : '#';
      ?>
        <a class="<?= $cls ?>" href="<?= $href ?>" tabindex="<?= $enabled ? '0' : '-1' ?>">
          <div class="card-icon"><?= $info['icon'] ?></div>
          <h3><?= $info['title'] ?></h3>
          <p><?= $info['desc'] ?></p>
        </a>
      <?php endforeach; ?>
    </div>

    <a class="logout" href="index.php?action=logout">Cerrar sesión</a>
  </div>
</body>
</html>
