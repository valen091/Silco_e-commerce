<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Include database configuration
    require_once 'config/database.php';
    
    // Create a new PDO instance
    $config = new DatabaseConfig();
    $dsn = "mysql:host={$config->getHost()};port={$config->getPort()};dbname={$config->getDbName()};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $config->getUsername(), $config->getPassword(), $options);
    
    // Check if favoritos table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'favoritos'");
    $favoritosTableExists = $stmt->rowCount() > 0;
    
    // List all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    // Output results
    echo "<h1>Database Tables</h1>";
    echo "<p>Connected to database: " . $config->getDbName() . "</p>";
    echo "<p>favoritos table exists: " . ($favoritosTableExists ? 'Yes' : 'No') . "</p>";
    
    echo "<h2>All Tables:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
