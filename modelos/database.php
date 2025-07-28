<?php
// modelos/database.php

class Database {
    /** @var \\PDO */
    private $pdo;
    /** @var Database */
    private static $instance = null;

    /** Constructor privado */
    private function __construct() {
        $dsn     = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => false,
        ];
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    /**
     * Devuelve la instancia de PDO (Singleton).
     *
     * @return \\PDO
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }

    // Después:
public function __clone() {}
public function __wakeup() {}
}
