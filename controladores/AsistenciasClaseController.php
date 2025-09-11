<?php
// controladores/AsistenciasClaseController.php

require_once __DIR__ . '/../config/database.php';

class AsistenciasClaseController {
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

    /* ===== Helper: obtener docente_id desde sesión ===== */
    private function docenteIdDesdeSesion(PDO $pdo): ?int {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) return null;

        // Relación: usuarios.persona_id ↔ docentes.persona_id
        $sql = "SELECT d.id
                  FROM usuarios u
                  JOIN docentes d ON d.persona_id = u.persona_id
                 WHERE u.id = :user_id
                 LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([':user_id' => $userId]);
        $id = $st->fetchColumn();

        return $id ? (int)$id : null;
    }

    /* ===== DOCENTE: vista de pase de lista por clase ===== */
    public function misAsistencias(): void {
        require_login();
        if (($_SESSION['rol'] ?? '') !== 'docente') {
            $_SESSION['error'] = 'Solo docentes.';
            header('Location: index.php?action=dashboard'); exit;
        }

        $pdo = $this->pdo();
        $claseId = (int)($_GET['clase_id'] ?? 0);
        if ($claseId <= 0) {
            $_SESSION['error'] = 'Clase inválida.';
            header('Location:index.php?action=docente_clases'); exit;
        }

        // Validar pertenencia de clase al docente
        $docenteId = $this->docenteIdDesdeSesion($pdo);
        if (!$docenteId) {
            $_SESSION['error'] = 'No se pudo determinar el docente.';
            header('Location:index.php?action=dashboard'); exit;
        }
        $stC = $pdo->prepare("SELECT 1 FROM clases WHERE id=:c AND docente_id=:d LIMIT 1");
        $stC->execute([':c'=>$claseId, ':d'=>$docenteId]);
        if (!$stC->fetchColumn()) {
            $_SESSION['error'] = 'No autorizado para esta clase.';
            header('Location:index.php?action=docente_clases'); exit;
        }

        // Datos de la clase (usa columnas reales; no existe 'nombre' en 'clases')
        $stInfo = $pdo->prepare("SELECT id, grupo_id, asignatura_id, aula, dia, horario_id FROM clases WHERE id=:c");
        $stInfo->execute([':c'=>$claseId]);
        $clase = $stInfo->fetch(PDO::FETCH_ASSOC);
        if (!$clase) {
            $_SESSION['error'] = 'Clase no encontrada.';
            header('Location:index.php?action=docente_clases'); exit;
        }

        // (Opcional) nombre de la asignatura
        $asignaturaNombre = null;
        if (!empty($clase['asignatura_id'])) {
            $stAsig = $pdo->prepare("SELECT nombre FROM asignaturas WHERE id = :a");
            $stAsig->execute([':a' => (int)$clase['asignatura_id']]);
            $asignaturaNombre = $stAsig->fetchColumn() ?: null;
        }

// === LISTA DE ESTUDIANTES POR GRUPO DE LA CLASE ===
// estudiantes.grupo_id = clases.grupo_id
$sqlEst = "
    SELECT 
        e.id,
        e.NIE AS nie,                  -- <== alias en minúsculas
        per.nombre AS nombre
    FROM estudiantes e
    JOIN personas  per ON per.id = e.persona_id
    WHERE e.grupo_id = :g
    ORDER BY per.nombre
";
$stE = $pdo->prepare($sqlEst);
$stE->execute([':g' => (int)$clase['grupo_id']]);
$estudiantes = $stE->fetchAll();
       

        // Asistencias de HOY (tu esquema usa columna 'fecha' generada desde registrada_en)
        $hoy = date('Y-m-d');
        $stA = $pdo->prepare("
            SELECT estudiante_id, estado
            FROM asistencias_clase
            WHERE clase_id=:c AND fecha=:f
        ");
        $stA->execute([':c'=>$claseId, ':f'=>$hoy]);
        $asisHoy = [];
        foreach ($stA->fetchAll() as $r) {
            $asisHoy[(int)$r['estudiante_id']] = $r['estado'];
        }

        // Variables para la vista
        $claseIdView     = $claseId;
        $asignaturaView  = $asignaturaNombre;

        // Vista existente en tu proyecto
        require __DIR__ . '/../views/Docentes/asistencias.php';
    }

    // POST index.php?action=asistencias_store
    public function store(): void {
        require_login();
        $rol = $_SESSION['rol'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=clases_index'); exit;
        }

        $pdo = $this->pdo();

        $clase_id    = (int)($_POST['clase_id'] ?? 0);
        $fecha       = trim((string)($_POST['fecha'] ?? date('Y-m-d')));
        $tipo_global = $_POST['tipo_global'] ?? null; // presente | ausente | justificado
        $obs_global  = trim((string)($_POST['observacion'] ?? ''));
        $ids         = $_POST['estudiante_ids'] ?? []; // array de IDs seleccionados
        $tipos_fila  = $_POST['tipo'] ?? [];           // overrides por fila
        $obs_fila    = $_POST['obs']  ?? [];           // overrides por fila

        if ($clase_id <= 0 || !is_array($ids) || empty($ids)) {
            $_SESSION['flash_msg'] = 'Selecciona al menos un estudiante.';
            $to = ($rol === 'docente') ? 'docente_asistencias&clase_id='.$clase_id : 'clases_show&id='.$clase_id;
            header('Location: index.php?action='.$to.'&fecha='.$fecha); exit;
        }

        // Si es docente, validar que la clase le pertenece
        if ($rol === 'docente') {
            $docenteId = $this->docenteIdDesdeSesion($pdo);
            if (!$docenteId) {
                $_SESSION['flash_msg'] = 'No se pudo determinar el docente.';
                header('Location:index.php?action=dashboard'); exit;
            }
            $stC = $pdo->prepare("SELECT 1 FROM clases WHERE id=:c AND docente_id=:d LIMIT 1");
            $stC->execute([':c'=>$clase_id, ':d'=>$docenteId]);
            if (!$stC->fetchColumn()) {
                $_SESSION['flash_msg'] = 'No autorizado para guardar en esta clase.';
                header('Location: index.php?action=docente_clases'); exit;
            }
        } else {
            require_admin();
        }

        $valid = ['presente','ausente','justificado'];

        $sql = "INSERT INTO asistencias_clase (clase_id, estudiante_id, fecha, estado, observacion, registrado_por, registrada_en)
                VALUES (:clase_id, :est_id, :fecha, :estado, :obs, :user, NOW())
                ON DUPLICATE KEY UPDATE estado=VALUES(estado), observacion=VALUES(observacion), registrado_por=VALUES(registrado_por)";
        $st  = $pdo->prepare($sql);
        $user_id = (int)($_SESSION['user_id'] ?? 0);

        $okCount = 0; 
        $skip = 0;

        foreach ($ids as $est_id) {
            $eid    = (int)$est_id;
            $estado = $tipos_fila[$eid] ?? $tipo_global;
            if (!in_array($estado, $valid, true)) { $skip++; continue; }
            $obs    = $obs_fila[$eid] ?? $obs_global;

            $st->execute([
                ':clase_id' => $clase_id,
                ':est_id'   => $eid,
                ':fecha'    => $fecha,
                ':estado'   => $estado,
                ':obs'      => ($obs !== '' ? $obs : null),
                ':user'     => $user_id ?: null
            ]);
            $okCount++;
        }

        $_SESSION['flash_msg'] = "Asistencias guardadas: {$okCount}" . ($skip ? " (omitidas: {$skip})" : '');

        if ($rol === 'docente') {
            header('Location: index.php?action=docente_asistencias&clase_id='.$clase_id.'&fecha='.$fecha);
        } else {
            header('Location: index.php?action=clases_show&id='.$clase_id.'&fecha='.$fecha);
        }
        exit;
    }
}