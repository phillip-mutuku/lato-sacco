<?php
// Load .env file and parse variables
function loadEnv($path) {
    if (!file_exists($path)) return;
    
    static $loaded = false;
    if ($loaded) return; // Prevent multiple loads
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
    $loaded = true;
}

// Load environment variables
loadEnv(__DIR__ . '/../.env');

// Define database constants
if (!defined('DB_HOST')) {
    define("DB_HOST", getenv('DB_HOST') ?: 'localhost');
    define("DB_USER", getenv('DB_USER') ?: 'root');
    define("DB_PASS", getenv('DB_PASS') ?: '');
    define("DB_NAME", getenv('DB_NAME') ?: 'db_lms');
}

// Optimized Database Connection with Connection Pooling
if (!class_exists('db_connect')) {
    class db_connect {
        private static $instance = null;
        private static $connections = [];
        private static $maxConnections = 10;
        public $conn;
        public $error;
        
        public function connect() {
            // Check if we already have a connection
            if ($this->conn && $this->conn->ping()) {
                return $this->conn;
            }
            
            // Try to get a connection from pool
            foreach (self::$connections as $index => $connection) {
                if ($connection && $connection->ping()) {
                    $this->conn = $connection;
                    unset(self::$connections[$index]);
                    return $this->conn;
                }
            }
            
            // Create new connection if pool is empty
            try {
                $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                
                if ($this->conn->connect_error) {
                    throw new Exception("Connection failed: " . $this->conn->connect_error);
                }
                
                // Optimize connection settings
                $this->conn->set_charset("utf8mb4");
                $this->conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
                
                return $this->conn;
                
            } catch (Exception $e) {
                $this->error = "Fatal Error: Can't connect to database: " . $e->getMessage();
                error_log($this->error);
                return false;
            }
        }
        
        public function __destruct() {
            // Return connection to pool instead of closing
            if ($this->conn && count(self::$connections) < self::$maxConnections) {
                self::$connections[] = $this->conn;
            }
        }
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
    }
}
?>