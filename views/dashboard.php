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
        // Definición completa (admin y otros roles intactos)
        $all = [
          // Común / Kiosco
          'asistencia_registro' => [
            'title' => 'Asistencia',
            'desc'  => 'Marcar entrada/salida por NIE',
            'icon'  => '🕒',
            'enabled_roles' => ['admin','docente','orientador','directora','estudiante']
          ],

          // Admin
          'estudiantes_index' => ['title'=>'Estudiantes','desc'=>'Registrar y gestionar estudiantes','icon'=>'🎓','enabled_roles'=>['admin']],
          'docentes_index'    => ['title'=>'Docentes','desc'=>'Listado y gestión de docentes','icon'=>'👨‍🏫','enabled_roles'=>['admin']],
          'grupos_index'      => ['title'=>'Grupos','desc'=>'Secciones, grados y asignación','icon'=>'👥','enabled_roles'=>['admin']],
          'usuarios_index'    => ['title'=>'Usuarios','desc'=>'Cuentas, roles y permisos','icon'=>'🧑‍💻','enabled_roles'=>['admin']],
          'clases_index'      => ['title'=>'Clases','desc'=>'Horario y asignaturas','icon'=>'📚','enabled_roles'=>['admin']],

          // Reportes (admin + docente + otros)
          'reportes' => [
            'title' => 'Reportes',
            'desc'  => 'Estadísticas e informes',
            'icon'  => '📈',
            'enabled_roles' => ['admin','docente','orientador','directora']
          ],

          // Docente
          'docente_clases' => [
            'title' => 'Mis Clases',
            'desc'  => 'Ver grupos y estudiantes asignados',
            'icon'  => '📚',
            'enabled_roles' => ['docente']
          ]
        ];

        // --- Ajuste: si es DOCENTE, solo 3 tarjetas ---
        if (($rol ?? null) === 'docente') {
          $permitidas = ['asistencia_registro','reportes','docente_clases'];
          $all = array_intersect_key($all, array_flip($permitidas));
        }

        // Render de tarjetas según roles permitidos
        foreach ($all as $action => $info):
          $enabled = in_array($rol, $info['enabled_roles'], true);
          if (!$enabled) continue;
          $href = "index.php?action={$action}";
      ?>
        <a class="card" href="<?= $href ?>" tabindex="0">
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