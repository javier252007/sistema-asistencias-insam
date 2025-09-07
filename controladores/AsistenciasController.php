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
      $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
      $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
      ]);
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

    try {
      if (!$this->pdo) { echo json_encode(['ok'=>true,'data'=>[]]); return; }
      $lista = (new Estudiante($this->pdo))->findByNiePrefix($nie, 10);
      echo json_encode(['ok'=>true,'data'=>$lista]);
    } catch (Throwable $e) {
      error_log('[buscarEstudiante] '.$e->getMessage());
      echo json_encode(['ok'=>false,'error'=>'Error al buscar.']);
    }
  }

  public function marcarEntrada(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=asistencia_registro'); exit; }
    if (!$this->pdo) { $_SESSION['flash_msg']='No hay conexión a BD.'; header('Location: index.php?action=asistencia_registro'); exit; }

    $id = (int)($_POST['estudiante_id'] ?? 0);
    try {
      $est = (new Estudiante($this->pdo))->findById($id);
      if (!$est) { $_SESSION['flash_msg']='El estudiante no existe.'; header('Location: index.php?action=asistencia_registro'); exit; }
      if (($est['estado'] ?? 'activo') !== 'activo') { $_SESSION['flash_msg']='Estudiante inactivo.'; header('Location: index.php?action=asistencia_registro'); exit; }

      $asis = new AsistenciaInstitucional($this->pdo);
      if ($asis->existeEntradaHoy($id)) {
        $_SESSION['flash_msg'] = 'Ya registraste tu entrada hoy.';
        header('Location: index.php?action=asistencia_registro'); exit;
      }
      if ($asis->existeSalidaHoy($id)) {
        $_SESSION['flash_msg'] = 'Ya registraste tu salida hoy; no puedes marcar entrada.';
        header('Location: index.php?action=asistencia_registro'); exit;
      }

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
    if (!$this->pdo) { $_SESSION['flash_msg']='No hay conexión a BD.'; header('Location: index.php?action=asistencia_registro'); exit; }

    $id = (int)($_POST['estudiante_id'] ?? 0);
    try {
      $est = (new Estudiante($this->pdo))->findById($id);
      if (!$est) { $_SESSION['flash_msg']='El estudiante no existe.'; header('Location: index.php?action=asistencia_registro'); exit; }
      if (($est['estado'] ?? 'activo') !== 'activo') { $_SESSION['flash_msg']='Estudiante inactivo.'; header('Location: index.php?action=asistencia_registro'); exit; }

      $asis = new AsistenciaInstitucional($this->pdo);
      if (!$asis->existeEntradaHoy($id)) {
        $_SESSION['flash_msg'] = 'No puedes marcar salida sin haber marcado entrada hoy.';
        header('Location: index.php?action=asistencia_registro'); exit;
      }
      if ($asis->existeSalidaHoy($id)) {
        $_SESSION['flash_msg']='Ya registraste tu salida hoy.';
        header('Location: index.php?action=asistencia_registro'); exit;
      }

      $asis->marcarSalida($id);
      $_SESSION['flash_msg'] = 'Salida registrada. ¡Hasta luego! 👋';
      header('Location: index.php?action=asistencia_registro'); exit;

    } catch (Throwable $e) {
      error_log('[marcarSalida] '.$e->getMessage());
      $_SESSION['flash_msg'] = 'Ocurrió un error al guardar la salida.';
      header('Location: index.php?action=asistencia_registro'); exit;
    }
  }

  /**
   * HISTORIAL GENERAL (una fila por estudiante).
   * - Sin repetir estudiante.
   * - Muestra su ÚLTIMO registro de asistencia (fecha/hora).
   * - Filtros: q (nombre/NIE) y fecha (si se pasa, solo alumnos con registro ese día).
   */
  public function historial(): void {
    header('Content-Type: text/html; charset=utf-8');

    $q      = trim($_GET['q']     ?? '');
    $fecha  = trim($_GET['fecha'] ?? '');
    $page   = max(1, (int)($_GET['page']    ?? 1));
    $per    = (int)($_GET['perPage'] ?? 25);
    if ($per < 10) $per = 10; if ($per > 200) $per = 200;
    $off    = ($page - 1) * $per;

    $where  = [];
    $params = [];

    if ($q !== '') {
      $where[] = "(p.nombre LIKE :q OR e.NIE LIKE :q2)";
      $params[':q']  = '%'.$q.'%';
      $params[':q2'] = '%'.$q.'%';
    }
    if ($fecha !== '') {
      // Restringe al día indicado
      $where[] = "DATE(a.fecha_hora) = :fecha";
      $params[':fecha'] = $fecha;
    }
    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

    // Una fila por estudiante, mostrando su último registro (MAX(fecha_hora))
    $sql = "SELECT 
              e.id   AS estudiante_id,
              e.NIE,
              p.nombre AS estudiante,
              MAX(a.fecha_hora) AS ultima_fecha
            FROM asistencias_institucionales a
            JOIN estudiantes e ON e.id = a.estudiante_id
            JOIN personas   p  ON p.id = e.persona_id
            $whereSql
            GROUP BY e.id, e.NIE, p.nombre
            ORDER BY ultima_fecha DESC
            LIMIT :lim OFFSET :off";
    $st = $this->pdo->prepare($sql);
    foreach ($params as $k=>$v) { $st->bindValue($k, $v); }
    $st->bindValue(':lim', max(0,$per),  PDO::PARAM_INT);
    $st->bindValue(':off', max(0,$off),  PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll();

    // Total de estudiantes distintos que cumplen los filtros
    $stc = $this->pdo->prepare("SELECT COUNT(*) AS c
                                  FROM (
                                    SELECT e.id
                                      FROM asistencias_institucionales a
                                      JOIN estudiantes e ON e.id = a.estudiante_id
                                      JOIN personas   p  ON p.id = e.persona_id
                                      $whereSql
                                  GROUP BY e.id
                                  ) t");
    foreach ($params as $k=>$v) { $stc->bindValue($k, $v); }
    $stc->execute();
    $total = (int)$stc->fetchColumn();
    $pages = (int)ceil($total / ($per ?: 1));

    $result = ['data'=>$rows,'page'=>$page,'pages'=>$pages,'total'=>$total,'perPage'=>$per];
    require __DIR__ . '/../views/asistencias/historial.php';
  }

  /**
   * HISTORIAL POR ESTUDIANTE (detalle).
   * Parámetros: id (obligatorio), fecha (opcional), perPage/page (opcional)
   */
  public function historialEstudiante(): void {
    header('Content-Type: text/html; charset=utf-8');

    $id   = max(0, (int)($_GET['id'] ?? 0));
    if ($id <= 0) {
      echo "<p>Estudiante inválido.</p>";
      return;
    }

    $fecha = trim($_GET['fecha'] ?? '');
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $per   = (int)($_GET['perPage'] ?? 25);
    if ($per < 10) $per = 10; if ($per > 200) $per = 200;
    $off   = ($page - 1) * $per;

    $est = (new Estudiante($this->pdo))->findById($id);
    if (!$est) {
      echo "<p>Estudiante no encontrado.</p>";
      return;
    }

    $where = ["a.estudiante_id = :id"];
    $params = [':id' => $id];
    if ($fecha !== '') {
      $where[] = "DATE(a.fecha_hora) = :fecha";
      $params[':fecha'] = $fecha;
    }
    $whereSql = 'WHERE '.implode(' AND ', $where);

    $sql = "SELECT a.id, a.tipo, a.fecha_hora
              FROM asistencias_institucionales a
              $whereSql
          ORDER BY a.fecha_hora DESC
             LIMIT :lim OFFSET :off";
    $st = $this->pdo->prepare($sql);
    foreach ($params as $k=>$v) { $st->bindValue($k, $v); }
    $st->bindValue(':lim', max(0,$per),  PDO::PARAM_INT);
    $st->bindValue(':off', max(0,$off),  PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll();

    $stc = $this->pdo->prepare("SELECT COUNT(*) FROM asistencias_institucionales a $whereSql");
    foreach ($params as $k=>$v) { $stc->bindValue($k, $v); }
    $stc->execute();
    $total = (int)$stc->fetchColumn();
    $pages = (int)ceil($total / ($per ?: 1));

    $result = ['data'=>$rows,'page'=>$page,'pages'=>$pages,'total'=>$total,'perPage'=>$per];
    $estudiante = $est;
    require __DIR__ . '/../views/asistencias/historial_estudiante.php';
  }
}
