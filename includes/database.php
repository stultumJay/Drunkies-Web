<?php
require_once __DIR__ . '/../config/database.php';

// Flow: Database Connection Manager
// Implements Singleton pattern for database connections

// 1. Include database configuration

// 2. Database Class Definition
class Database {
    // Flow: Single instance of the database connection
    private static $instance = null;
    private $conn;

    // 3. Private Constructor
    // Flow: Creates new database connection using config values
    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    // 4. Singleton Instance Method
    // Flow: Returns existing instance or creates new one
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // 5. Connection Access Methods
    // Flow: Get raw database connection
    public function getConnection() {
        return $this->conn;
    }

    // Flow: Execute direct SQL query
    public function query($sql) {
        return $this->conn->query($sql);
    }

    // Flow: Prepare SQL statement for parameterized queries
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    // Flow: Escape strings to prevent SQL injection
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
} 