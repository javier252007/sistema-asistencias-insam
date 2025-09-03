<?php
require_once __DIR__ . '/../modelos/Estudiante.php';
require_once __DIR__ . '/../modelos/AsistenciaInstitucional.php';
require_once __DIR__ . '/../config/database.php';

class AsistenciasController {
  /** @var ?PDO */
  private $pdo = null;

  public function __construct() {
    try {
      if (class_exists('Database') && method_exists('Database','getInstance')) {
        $pdo = Database::getInstance();
        if ($pdo instanceof PDO) { $this->pdo = $pdo; return; }
      }
      if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        $this->pdo = $GLOBALS['pdo']; return;
      }
      if (defined('DB_HOST')) {
        $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
          PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES=>false,
        ]);
        return;
      }
    } catch (Throwable $e) {
      error_log('[AsistenciasController::__construct] '.$e->getMessage());
      $this->pdo = null;
    }
  }

  public function registro(): void {
    $mensaje = $_SESSION['flash_msg'] ?? null;
    unset($_SESSION['flash_msg']);
    require __DIR__ . '/../views/asistencias/registro.php';
  }

  public function buscarEstudiante(): void {
    header('Content-Type: application/json; charset=utf-8');
    $nie = trim($_GET['nie'] ?? '');
    if ($nie === '' || strlen($nie) > 50) { echo json_encode(['ok'=>true,'data'=>[]]); return; }
    if (!$this->pdo) { echo json_encode(['ok'=>false,'error'=>'Conexión no disponible']); return; }
    try {
      $lista = (new Estudiante($this->pdo))->findByNiePrefix($nie, 10);
      echo json_encode(['ok'=>true,'data'=>$lista]);
    } catch (Throwable $e) {
      error_log('[buscarEstudiante] '.$e->getMessage());
      http_response_code(500);
      echo json_encode(['ok'=>false,'error'=>'Error en la búsqueda']);
    }
  }

  public function marcarEntrada(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=asistencia_registro'); exit; }
    if (!$this->pdo) { $_SESSION['flash_msg']='No hay conexión a base de datos.'; header('Location: index.php?action=asistencia_registro'); exit; }

    $id = (int)($_POST['estudiante_id'] ?? 0);
    if ($id <= 0) { $_SESSION['flash_msg']='Estudiante inválido.'; header('Location: index.php?action=asistencia_registro'); exit; }

    try {
      $est = (new Estudiante($this->pdo))->findById($id);
      if (!$est) { $_SESSION['flash_msg']='El estudiante no existe.'; header('Location: index.php?action=asistencia_registro'); exit; }
      if (($est['estado'] ?? 'activo') !== 'activo') { $_SESSION['flash_msg']='El estudiante no está activo.'; header('Location: index.php?action=asistencia_registro'); exit; }

      $asis = new AsistenciaInstitucional($this->pdo);

      // ✅ Validaciones del día
      $hayEntrada = $asis->existeEntradaHoy($id);
      $haySalida  = $asis->existeSalidaHoy($id);

      if ($hayEntrada) {
        $_SESSION['flash_msg'] = 'Ya registraste tu entrada hoy.';
        header('Location: index.php?action=asistencia_registro'); exit;
      }

      // 🔴 Si ya hay SALIDA hoy, no permitir marcar entrada
      if ($haySalida) {
        $_SESSION['flash_msg'] = 'Ya registraste tu salida hoy; no puedes marcar entrada.';
        header('Location: index.php?action=asistencia_registro'); exit;
      }

      // Guardar entrada
      $asis->marcarEntrada($id);
      $_SESSION['flash_msg'] = 'Entrada registrada correctamente. ¡Bienvenido! 👋';
      header('Location: index.php?action=asistencia_registro'); exit;

    } catch (Throwable $e) {
      error_log('[marcarEntrada] '.$e->getMessage());
      $_SESSION['flash_msg'] = 'Ocurrió un error al guardar la asistencia.';
      header('Location: index.php?action=asistencia_registro'); exit;
    }
  }

  public function marcarSalida(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=asistencia_registro'); exit; }
    if (!$this->pdo) { $_SESSION['flash_msg']='No hay conexión a base de datos.'; header('Location: index.php?action=asistencia_registro'); exit; }

    $id = (int)($_POST['estudiante_id'] ?? 0);
    if ($id <= 0) { $_SESSION['flash_msg']='Estudiante inválido.'; header('Location: index.php?action=asistencia_registro'); exit; }

    try {
      $est = (new Estudiante($this->pdo))->findById($id);
      if (!$est) { $_SESSION['flash_msg']='El estudiante no existe.'; header('Location: index.php?action=asistencia_registro'); exit; }
      if (($est['estado'] ?? 'activo') !== 'activo') { $_SESSION['flash_msg']='El estudiante no está activo.'; header('Location: index.php?action=asistencia_registro'); exit; }

      $asis = new AsistenciaInstitucional($this->pdo);

      // 🔴 Debe existir ENTRADA hoy para poder salir
      $hayEntrada = $this->pdo->prepare("
        SELECT 1
        FROM asistencias_institucionales
        WHERE estudiante_id = :id
          AND tipo = 'entrada'
          AND DATE(fecha_hora) = CURDATE()
        LIMIT 1
      ");
      $hayEntrada->execute([':id' => $id]);
      if (!$hayEntrada->fetchColumn()) {
        $_SESSION['flash_msg'] = 'No puedes marcar salida sin haber marcado entrada hoy.';
        header('Location: index.php?action=asistencia_registro'); exit;
      }

      // Evitar doble salida en el día
      if ($asis->existeSalidaHoy($id)) {
        $_SESSION['flash_msg']='Ya registraste tu salida hoy.';
        header('Location: index.php?action=asistencia_registro'); exit;
      }

      // Guardar salida
      $asis->marcarSalida($id);
      $_SESSION['flash_msg'] = 'Salida registrada. ¡Hasta luego! 👋';
      header('Location: index.php?action=asistencia_registro'); exit;

    } catch (Throwable $e) {
      error_log('[marcarSalida] '.$e->getMessage());
      $_SESSION['flash_msg'] = 'Ocurrió un error al guardar la salida.';
      header('Location: index.php?action=asistencia_registro'); exit;
    }
  }
}
