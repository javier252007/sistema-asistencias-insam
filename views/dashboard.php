<!-- views/dashboard.php -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard INSAM</title>
  <!-- Asegúrate de que el css está en public/css/dashboard.css -->
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
  <div class="container">
    <h1>Panel de Control</h1>
    <p>Bienvenido, tu rol es: <strong><?= htmlspecialchars($rol) ?></strong></p>

    <div class="cards-wrap">
      <?php
      // Definimos las tarjetas con su acción, título, descripción e icono
      $cards = [
        'gestionarDocentes'    => ['title'=>'Docentes',    'desc'=>'Agregar o modificar docentes',    'icon'=>'👩‍🏫'],
        'gestionarGrupos'      => ['title'=>'Grupos',      'desc'=>'Crear y asignar secciones',      'icon'=>'🏷️'],
        'gestionarEstudiantes' => ['title'=>'Estudiantes', 'desc'=>'Registrar o editar alumnos',      'icon'=>'🎓'],
        'gestionarUsuarios'    => ['title'=>'Usuarios',    'desc'=>'Administrar cuentas y roles',     'icon'=>'👤'],
        'gestionarClases'      => ['title'=>'Clases',      'desc'=>'Configurar horarios y asignaturas','icon'=>'📚'],
      ];

      foreach ($cards as $action => $info):
        // Solo admin ve todas; docente solo 'gestionarClases'
        $enabled = ($rol === 'admin') || ($rol === 'docente' && $action === 'gestionarClases');
        $cls     = $enabled ? 'card' : 'card disabled';
        $href    = $enabled ? "index.php?action={$action}" : '#';
      ?>
        <a class="<?= $cls ?>" href="<?= $href ?>" tabindex="<?= $enabled ? '0' : '-1' ?>">
          <div class="card-icon"><?= $info['icon'] ?></div>
          <h3><?= $info['title'] ?></h3>
          <p><?= $info['desc'] ?></p>
        </a>
      <?php endforeach; ?>
    </div>

    <a class="logout" href="logout.php">Cerrar sesión</a>
  </div>
</body>
</html>
