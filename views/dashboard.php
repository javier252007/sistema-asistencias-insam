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
        // Cada clave es la "action" que esperará el router (public/index.php)
        $all = [
          // NUEVO: Asistencia (kiosco por NIE)
          'asistencia_registro' => [
            'title' => 'Asistencia',
            'desc'  => 'Marcar entrada por NIE',
            'icon'  => '🕒',
            // Muestra a admin y, si quieres, a otros roles también:
            'enabled_roles' => ['admin','docente','orientador','directora','estudiante']
          ],

          'estudiantes_index' => [
            'title' => 'Estudiantes',
            'desc'  => 'Registrar y gestionar estudiantes',
            'icon'  => '🎓',
            'enabled_roles' => ['admin']
          ],
          'docentes_index' => [
            'title' => 'Docentes',
            'desc'  => 'Listado y gestión de docentes',
            'icon'  => '👨‍🏫',
            'enabled_roles' => ['admin']
          ],
          'grupos_index' => [
            'title' => 'Grupos',
            'desc'  => 'Secciones, grados y asignación',
            'icon'  => '👥',
            'enabled_roles' => ['admin']
          ],
          'usuarios_index' => [
            'title' => 'Usuarios',
            'desc'  => 'Cuentas, roles y permisos',
            'icon'  => '🧑‍💻',
            'enabled_roles' => ['admin']
          ],
          'clases_index' => [
            'title' => 'Clases',
            'desc'  => 'Horario y asignaturas',
            'icon'  => '📚',
            'enabled_roles' => ['admin']
          ],
          'reportes' => [
            'title' => 'Reportes',
            'desc'  => 'Estadísticas e informes',
            'icon'  => '📈',
            'enabled_roles' => ['admin','docente','orientador','directora']
          ],
        ];

        foreach ($all as $action => $info):
          $enabled = in_array($rol, $info['enabled_roles'], true);
          $cls     = 'card' . ($enabled ? '' : ' disabled');
          $href    = $enabled ? "index.php?action={$action}" : '#';
      ?>
        <a class="<?= $cls ?>" href="<?= $href ?>" tabindex="<?= $enabled ? '0' : '-1' ?>">
          <div class="card-icon"><?= $info['icon'] ?></div>
          <h3><?= htmlspecialchars($info['title']) ?></h3>
          <p><?= htmlspecialchars($info['desc']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>

    <a class="logout" href="index.php?action=logout">Cerrar sesión</a>
  </div>
</body>
</html>
