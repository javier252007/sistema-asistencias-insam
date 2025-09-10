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

    // POST index.php?action=asistencias_store
    public function store(): void {
        require_login(); require_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=clases_index'); exit;
        }

        $pdo = $this->pdo();

        $clase_id    = (int)($_POST['clase_id'] ?? 0);
        $fecha       = trim((string)($_POST['fecha'] ?? date('Y-m-d')));
        $tipo_global = $_POST['tipo_global'] ?? null; // presente/ausente/justificado
        $obs_global  = trim((string)($_POST['observacion'] ?? ''));
        $ids         = $_POST['estudiante_ids'] ?? []; // array de IDs seleccionados
        $tipos_fila  = $_POST['tipo'] ?? [];           // overrides por fila
        $obs_fila    = $_POST['obs']  ?? [];           // overrides por fila

        if ($clase_id <= 0 || !is_array($ids) || empty($ids)) {
            $_SESSION['flash_msg'] = 'Selecciona al menos un estudiante.';
            header('Location: index.php?action=clases_show&id='.$clase_id.'&fecha='.$fecha); exit;
        }

        $valid = ['presente','ausente','justificado'];
        $sql = "INSERT INTO asistencias_clase (clase_id, estudiante_id, fecha, tipo, observacion, registrado_por)
                VALUES (:clase_id, :est_id, :fecha, :tipo, :obs, :user)
                ON DUPLICATE KEY UPDATE tipo=VALUES(tipo), observacion=VALUES(observacion)";
        $st  = $pdo->prepare($sql);
        $user_id = (int)($_SESSION['user_id'] ?? 0);

        $okCount=0; $skip=0;
        foreach ($ids as $est_id) {
            $eid  = (int)$est_id;
            $tipo = $tipos_fila[$eid] ?? $tipo_global;
            if (!in_array($tipo, $valid, true)) { $skip++; continue; }
            $obs  = $obs_fila[$eid] ?? $obs_global;
            $st->execute([
                ':clase_id'=>$clase_id,
                ':est_id'  =>$eid,
                ':fecha'   =>$fecha,
                ':tipo'    =>$tipo,
                ':obs'     =>$obs !== '' ? $obs : null,
                ':user'    =>$user_id ?: null
            ]);
            $okCount++;
        }

        $_SESSION['flash_msg'] = "Asistencias guardadas: {$okCount}" . ($skip ? " (omitidas: {$skip})" : '');
        header('Location: index.php?action=clases_show&id='.$clase_id.'&fecha='.$fecha);
    }
}
