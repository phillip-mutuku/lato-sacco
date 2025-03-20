<?php
// Load .env file and parse variables
function loadEnv($path) {
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        list($key, $value) = explode('=', $line, 2);
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// Load environment variables from .env file in the root directory
loadEnv(__DIR__ . '/../.env');

// Define database constants using environment variables
if (!defined('db_host')) {
    define("db_host", getenv('DB_HOST') ?: 'localhost');
}
if (!defined('db_user')) {
    define("db_user", getenv('DB_USER') ?: 'root');
}
if (!defined('db_pass')) {
    define("db_pass", getenv('DB_PASS') ?: '');
}
if (!defined('db_name')) {
    define("db_name", getenv('DB_NAME') ?: 'db_lms');
}

// Database connection class
if (!class_exists('db_connect')) {
    class db_connect {
        public $conn;
        public $error;

        public function connect() {
            $this->conn = new mysqli(db_host, db_user, db_pass, db_name);

            if ($this->conn->connect_error) {
                $this->error = "Fatal Error: Can't connect to database: " . $this->conn->connect_error;
                return false;
            }
            return $this->conn;
        }
    }
}
?>