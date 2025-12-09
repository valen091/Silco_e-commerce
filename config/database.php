<?php
class DatabaseConfig {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct() {
        $this->loadEnv();
        $this->host = getenv('DB_HOST');
        $this->db_name = getenv('DB_DATABASE');
        $this->username = getenv('DB_USER');
        $this->password = getenv('DB_PASSWORD');
        $this->port = getenv('DB_PORT') ?: '3306';
    }

    private function loadEnv() {
        // Default values
        $defaults = [
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'silco_db',
            'DB_USER' => 'root',
            'DB_PASSWORD' => '',
            'DB_PORT' => '3306'
        ];

        // Try to load from .env file if it exists
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value);
                        $defaults[$name] = $value;
                    }
                }
            }
        }

        // Set environment variables
        foreach ($defaults as $key => $value) {
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    public function connect() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos. Por favor, inténtelo de nuevo más tarde.");
        }

        return $this->conn;
    }
}
?>
