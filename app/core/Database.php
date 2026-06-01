<?php
/**
 * Clase Database - Singleton PDO
 * Gestiona la conexión a la base de datos con PDO
 */

class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die('Error de conexión a la base de datos: ' . $e->getMessage());
            } else {
                die('Error de conexión a la base de datos. Contacte al administrador.');
            }
        }
    }

    /**
     * Obtener instancia única (Singleton)
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Obtener la conexión PDO
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Ejecutar una consulta preparada con parámetros
     */
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Obtener un único registro
     */
    public function fetchOne(string $sql, array $params = []): array|false {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Obtener todos los registros
     */
    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Obtener el último ID insertado
     */
    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }

    // Prevenir clonación del Singleton
    private function __clone() {}
}
