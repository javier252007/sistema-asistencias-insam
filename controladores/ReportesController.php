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
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return $this->pdo;
    }

    /* =========================
       Helpers de sesión/seguridad
       ========================= */
    private function docenteIdDesdeSesion(PDO $pdo): ?int {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) return null;
        $sql = "SELECT d.id
                  FROM usuarios u
                  JOIN docentes d ON d.persona_id = u.persona_id
                 WHERE u.id = :uid
                 LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([':uid'=>$userId]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }

    private function assertClasePerteneceADocente(PDO $pdo, int $claseId, int $docenteId): void {
        if ($claseId <= 0) return;
        $st = $pdo->prepare("SELECT 1 FROM clases WHERE id=:c AND docente_id=:d LIMIT 1");
        $st->execute([':c'=>$claseId, ':d'=>$docenteId]);
        if (!$st->fetchColumn()) {
            $_SESSION['error'] = 'No autorizado para esa clase.';
            header('Location: index.php?action=reportes');
            exit;
        }
    }

    /* =========================
       Catálogo de clases
       - Si $docenteId es null => todas (admin)
       - Si $docenteId tiene valor => solo del docente
       ========================= */
    private function listarClases(PDO $pdo, ?int $docenteId = null): array {
        $where = '';
        $params = [];
        if ($docenteId) {
            $where = 'WHERE c.docente_id = :d';
            $params[':d'] = $docenteId;
        }

        $sql = "SELECT  c.id,
                        g.grado, g.seccion, g.anio_lectivo,
                        a.nombre AS asignatura,
                        h.hora_inicio, h.hora_fin,
                        p.nombre AS docente
                  FROM clases c
             LEFT JOIN grupos g      ON g.id = c.grupo_id
             LEFT JOIN asignaturas a ON a.id = c.asignatura_id
             LEFT JOIN horarios h    ON h.id = c.horario_id
             LEFT JOIN docentes d    ON d.id = c.docente_id
             LEFT JOIN personas p    ON p.id = d.persona_id
                {$where}
              ORDER BY g.grado, g.seccion, h.hora_inicio";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $r['label'] = trim(sprintf(
                "Clase #%d — %s %s · %s · %s-%s · %s",
                $r['id'],
                $r['grado'] ?? '', $r['seccion'] ?? '',
                $r['asignatura'] ?? '—',
                substr($r['hora_inicio'] ?? '', 0, 5),
                substr($r['hora_fin'] ?? '', 0, 5),
                $r['docente'] ?? '—'
            ));
        }
        return $rows;
    }

    /* =========================
       Consultas de datos
       ========================= */
    private function asistenciasDeClase(PDO $pdo, int $claseId, ?string $desde = null, ?string $hasta = null): array {
        $params = [':c'=>$claseId];
        $where  = "ac.clase_id = :c";
        if ($desde) { $where .= " AND DATE(ac.registrada_en) >= :d"; $params[':d'] = $desde; }
        if ($hasta) { $where .= " AND DATE(ac.registrada_en) <= :h"; $params[':h'] = $hasta; }

        $sql = "SELECT  DATE(ac.registrada_en) AS fecha,
                        TIME(ac.registrada_en) AS hora,
                        ac.estado,
                        e.id AS estudiante_id,
                        per.nombre AS estudiante
                  FROM asistencias_clase ac
             LEFT JOIN estudiantes e ON e.id = ac.estudiante_id
             LEFT JOIN personas per  ON per.id = e.persona_id
                 WHERE $where
              ORDER BY fecha DESC, hora DESC, estudiante";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    private function incidentesResumenPorClase(PDO $pdo, int $claseId, ?string $desde = null, ?string $hasta = null): array {
        $params = [':c'=>$claseId];
        $where  = "ie.clase_id = :c";
        if ($desde) { $where .= " AND ie.fecha >= :d"; $params[':d'] = $desde; }
        if ($hasta) { $where .= " AND ie.fecha <= :h"; $params[':h'] = $hasta; }

        $sql = "SELECT  e.id AS estudiante_id,
                        per.nombre AS estudiante,
                        COUNT(*) AS total,
                        MAX(CONCAT(ie.fecha,' ',COALESCE(ie.hora,'00:00:00'))) AS ultima_ts
                  FROM incidentes_estudiantes ie
             LEFT JOIN estudiantes e ON e.id = ie.estudiante_id
             LEFT JOIN personas per  ON per.id = e.persona_id
                 WHERE $where
              GROUP BY e.id, per.nombre
              ORDER BY ultima_ts DESC, estudiante ASC";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();

        foreach ($rows as &$r) {
            if (!empty($r['ultima_ts'])) {
                $dt = explode(' ', $r['ultima_ts']);
                $r['ultima_fecha'] = $dt[0] ?? '';
                $r['ultima_hora']  = $dt[1] ?? '';
            } else {
                $r['ultima_fecha'] = '';
                $r['ultima_hora']  = '';
            }
        }
        return $rows;
    }

    private function incidentesHistorialEstudiante(PDO $pdo, int $claseId, int $estudianteId, ?string $desde = null, ?string $hasta = null): array {
        $params = [':c'=>$claseId, ':e'=>$estudianteId];
        $where  = "ie.clase_id = :c AND ie.estudiante_id = :e";
        if ($desde) { $where .= " AND ie.fecha >= :d"; $params[':d'] = $desde; }
        if ($hasta) { $where .= " AND ie.fecha <= :h"; $params[':h'] = $hasta; }

        $sql = "SELECT  ie.fecha,
                        ie.hora,
                        COALESCE(NULLIF(tf.tipo,''), tf.descripcion, CONCAT('Tipo ', tf.id)) AS tipo,
                        ie.observacion
                  FROM incidentes_estudiantes ie
             LEFT JOIN tipos_falta tf ON tf.id = ie.falta_id
                 WHERE $where
              ORDER BY ie.fecha DESC, ie.hora DESC, ie.id DESC";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            if (!empty($r['tipo'])) $r['tipo'] = mb_convert_case($r['tipo'], MB_CASE_TITLE, "UTF-8");
        }
        return $rows;
    }

    /* =========================
       INDEX (Admin y Docente)
       ========================= */
    public function index(): void {
        require_login();
        $pdo = $this->pdo();

        $rol = $_SESSION['rol'] ?? '';
        $docenteId = null;
        if ($rol === 'docente') {
            $docenteId = $this->docenteIdDesdeSesion($pdo);
            if (!$docenteId) {
                $_SESSION['error'] = 'No se pudo determinar el docente.';
                header('Location: index.php?action=dashboard'); exit;
            }
        } elseif ($rol !== 'admin') {
            // Otros roles no acceden (ajusta si quieres permitir orientador/directora)
            require_admin();
        }

        // Clases visibles según el rol
        $clases = $this->listarClases($pdo, $docenteId);

        // Panel 1: Asistencia por clase
        $clase_id_a = isset($_GET['clase_id_a']) ? (int)$_GET['clase_id_a'] : 0;
        $desde_a    = $_GET['desde_a'] ?? null; if ($desde_a==='') $desde_a = null;
        $hasta_a    = $_GET['hasta_a'] ?? null; if ($hasta_a==='') $hasta_a = null;

        if ($rol === 'docente' && $clase_id_a > 0) {
            $this->assertClasePerteneceADocente($pdo, $clase_id_a, $docenteId);
        }

        $asisResultados = [];
        $claseSelA      = null;

        if ($clase_id_a > 0) {
            $asisResultados = $this->asistenciasDeClase($pdo, $clase_id_a, $desde_a, $hasta_a);
            foreach ($clases as $c) if ((int)$c['id'] === $clase_id_a) { $claseSelA = $c; break; }
        }

        // Panel 2: Incidentes por clase (resumen)
        $clase_id_i = isset($_GET['clase_id_i']) ? (int)$_GET['clase_id_i'] : 0;
        $desde_i    = $_GET['desde_i'] ?? null; if ($desde_i==='') $desde_i = null;
        $hasta_i    = $_GET['hasta_i'] ?? null; if ($hasta_i==='') $hasta_i = null;

        if ($rol === 'docente' && $clase_id_i > 0) {
            $this->assertClasePerteneceADocente($pdo, $clase_id_i, $docenteId);
        }

        $incResumen = [];
        $claseSelI  = null;

        if ($clase_id_i > 0) {
            $incResumen = $this->incidentesResumenPorClase($pdo, $clase_id_i, $desde_i, $hasta_i);
            foreach ($clases as $c) if ((int)$c['id'] === $clase_id_i) { $claseSelI = $c; break; }
        }

        require __DIR__ . '/../views/Reportes/index.php';
    }

    /* =========================
       Historial de incidentes (Admin y Docente)
       ========================= */
    public function incidentesHistorial(): void {
        require_login();
        $pdo = $this->pdo();

        $rol = $_SESSION['rol'] ?? '';
        $docenteId = null;
        if ($rol === 'docente') {
            $docenteId = $this->docenteIdDesdeSesion($pdo);
            if (!$docenteId) {
                $_SESSION['error']='No se pudo determinar el docente.';
                header('Location:index.php?action=dashboard'); exit;
            }
        } elseif ($rol !== 'admin') {
            require_admin();
        }

        $clases = $this->listarClases($pdo, $docenteId);

        $clase_id     = isset($_GET['clase_id']) ? (int)$_GET['clase_id'] : 0;
        $estudianteId = isset($_GET['estudiante_id']) ? (int)$_GET['estudiante_id'] : 0;
        $desde        = $_GET['desde'] ?? null; if ($desde==='') $desde = null;
        $hasta        = $_GET['hasta'] ?? null; if ($hasta==='') $hasta = null;

        if ($rol === 'docente' && $clase_id > 0) {
            $this->assertClasePerteneceADocente($pdo, $clase_id, $docenteId);
        }

        $claseSel = null;
        foreach ($clases as $c) if ((int)$c['id'] === $clase_id) { $claseSel = $c; break; }

        $estudianteNombre = null;
        if ($estudianteId > 0) {
            $stn = $pdo->prepare("SELECT per.nombre
                                    FROM estudiantes e
                                    JOIN personas per ON per.id=e.persona_id
                                   WHERE e.id=:e");
            $stn->execute([':e'=>$estudianteId]);
            $estudianteNombre = $stn->fetchColumn();
        }

        $historial = [];
        if ($clase_id > 0 && $estudianteId > 0) {
            $historial = $this->incidentesHistorialEstudiante($pdo, $clase_id, $estudianteId, $desde, $hasta);
        }

        require __DIR__ . '/../views/Reportes/incidentes_historial.php';
    }

    /* =========================
       DOCENTE: KPIs propios (se conserva)
       ========================= */
    public function docente(): void {
        require_login();
        if (($_SESSION['rol'] ?? '') !== 'docente') {
            $_SESSION['error']='Solo docentes.'; header('Location:index.php?action=dashboard'); exit;
        }
        $pdo = $this->pdo();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        // Resolver docente_id vía persona_id
        $qDoc = "SELECT d.id AS docente_id
                   FROM usuarios u
                   JOIN docentes d ON d.persona_id = u.persona_id
                  WHERE u.id = :user_id
                  LIMIT 1";
        $stDoc = $pdo->prepare($qDoc);
        $stDoc->execute([':user_id'=>$userId]);
        $doc = $stDoc->fetch(PDO::FETCH_ASSOC);
        if (!$doc) { $_SESSION['error']='No se encontró el docente.'; header('Location:index.php?action=dashboard'); exit; }
        $docenteId = (int)$doc['docente_id'];

        $kpis = ['clases'=>0,'estudiantes'=>0,'asistencia_hoy'=>null];

        $st1 = $pdo->prepare("SELECT COUNT(*) FROM clases WHERE docente_id=:d");
        $st1->execute([':d'=>$docenteId]); 
        $kpis['clases'] = (int)$st1->fetchColumn();

        $st2 = $pdo->prepare("SELECT COUNT(DISTINCT ce.estudiante_id)
                                FROM clases c
                                JOIN clases_estudiantes ce ON ce.clase_id=c.id
                               WHERE c.docente_id=:d");
        $st2->execute([':d'=>$docenteId]); 
        $kpis['estudiantes'] = (int)$st2->fetchColumn();

        $st3 = $pdo->prepare("SELECT 
                                SUM(CASE WHEN a.estado='presente' THEN 1 ELSE 0 END) AS p,
                                COUNT(*) AS total
                               FROM asistencias_clase a
                               JOIN clases c ON c.id=a.clase_id
                              WHERE c.docente_id=:d AND DATE(a.registrada_en)=CURDATE()");
        $st3->execute([':d'=>$docenteId]);
        $r3 = $st3->fetch(PDO::FETCH_ASSOC);
        if ($r3 && (int)$r3['total']>0) {
            $kpis['asistencia_hoy'] = round(($r3['p']*100)/$r3['total'],1);
        }

        require __DIR__ . '/../views/Reportes/docente.php';
    }
}