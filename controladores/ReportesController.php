<?php
// controladores/ReportesController.php
require_once __DIR__ . '/../config/database.php';

class ReportesController {
    private ?PDO $pdo = null;
    private function pdo(): PDO {
        if ($this->pdo instanceof PDO) return $this->pdo;
        if (class_exists('Database') && method_exists('Database','getInstance')) {
            $this->pdo = Database::getInstance();
        } else {
            $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false,
            ]);
        }
        return $this->pdo;
    }

    public function index(): void {
        // vista con formularios
        $resA = $_SESSION['reporte_inst'] ?? null;
        $resC = $_SESSION['reporte_clase'] ?? null;
        unset($_SESSION['reporte_inst'], $_SESSION['reporte_clase']);
        require __DIR__ . '/../views/Reportes/index.php';
    }

    /** Reporte de asistencia institucional por rango de fechas */
    public function generarInstitucional(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=reportes'); exit; }
        $f1 = $_POST['fecha_desde'] ?? '';
        $f2 = $_POST['fecha_hasta'] ?? '';
        if (!$f1 || !$f2) { $_SESSION['reporte_inst']=['error'=>'Debes indicar ambas fechas.']; header('Location: index.php?action=reportes'); exit; }

        $sql = "SELECT e.id AS estudiante_id, p.nombre, e.NIE,
                       DATE(ai.fecha_hora) AS fecha,
                       SUM(ai.tipo='entrada') AS entradas,
                       SUM(ai.tipo='salida')  AS salidas
                  FROM asistencias_institucionales ai
            INNER JOIN estudiantes e ON e.id = ai.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
                 WHERE DATE(ai.fecha_hora) BETWEEN :f1 AND :f2
              GROUP BY e.id, p.nombre, e.NIE, DATE(ai.fecha_hora)
              ORDER BY p.nombre ASC, fecha ASC";
        $st = $this->pdo()->prepare($sql);
        $st->execute([':f1'=>$f1, ':f2'=>$f2]);
        $rows = $st->fetchAll();

        $_SESSION['reporte_inst'] = ['ok'=>true, 'rows'=>$rows, 'f1'=>$f1, 'f2'=>$f2];
        header('Location: index.php?action=reportes');
    }

    /** Reporte de asistencia por clase y rango de fechas */
    public function generarPorClase(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=reportes'); exit; }

        $clase_id = (int)($_POST['clase_id'] ?? 0);
        $f1 = $_POST['fecha_desde_c'] ?? '';
        $f2 = $_POST['fecha_hasta_c'] ?? '';
        if ($clase_id<=0 || !$f1 || !$f2) { $_SESSION['reporte_clase']=['error'=>'Clase y fechas son obligatorias.']; header('Location: index.php?action=reportes'); exit; }

        // Cabecera clase
        $cab = $this->pdo()->prepare("
            SELECT c.id, pd.nombre AS docente,
                   CONCAT(g.grado,' ',g.seccion,' (',g.anio_lectivo,')') AS grupo,
                   a.nombre AS asignatura, c.dia, c.hora_inicio, c.hora_fin, c.aula
              FROM clases c
        INNER JOIN docentes d    ON d.id = c.docente_id
        INNER JOIN personas pd   ON pd.id = d.persona_id
        INNER JOIN grupos g      ON g.id = c.grupo_id
        LEFT  JOIN asignaturas a ON a.id = c.asignatura_id
             WHERE c.id = :id
             LIMIT 1
        ");
        $cab->execute([':id'=>$clase_id]);
        $clase = $cab->fetch();

        // Detalle asistencias
        $sql = "SELECT DATE(ac.registrada_en) AS fecha, e.NIE, p.nombre, ac.estado
                  FROM asistencias_clase ac
            INNER JOIN estudiantes e ON e.id = ac.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
                 WHERE ac.clase_id = :cid
                   AND DATE(ac.registrada_en) BETWEEN :f1 AND :f2
              ORDER BY fecha ASC, p.nombre ASC";
        $st = $this->pdo()->prepare($sql);
        $st->execute([':cid'=>$clase_id, ':f1'=>$f1, ':f2'=>$f2]);
        $rows = $st->fetchAll();

        $_SESSION['reporte_clase'] = ['ok'=>true, 'rows'=>$rows, 'f1'=>$f1, 'f2'=>$f2, 'clase'=>$clase];
        header('Location: index.php?action=reportes');
    }

    /** Para llenar el select de clases en la vista de reportes */
    public function listarClases(): array {
        $sql = "SELECT c.id,
                       CONCAT(pd.nombre,' — ', g.grado,' ',g.seccion,' (',g.anio_lectivo,')',' — ', IFNULL(a.nombre,'(sin asignatura)')) AS nombre
                  FROM clases c
            INNER JOIN docentes d    ON d.id = c.docente_id
            INNER JOIN personas pd   ON pd.id = d.persona_id
            INNER JOIN grupos g      ON g.id = c.grupo_id
            LEFT  JOIN asignaturas a ON a.id = c.asignatura_id
              ORDER BY pd.nombre ASC, g.grado ASC, g.seccion ASC";
        return $this->pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
