<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load main configuration
require_once __DIR__ . '/config.php';

echo "<h2>Fixing Favoritos Table - Direct Fix</h2>";

try {
    // Use database configuration from config.php
    $dbConfig = [
        'host' => DB_HOST,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS
    ];
    
    // Create a direct PDO connection
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    
    // Check if the column exists
    $check = $pdo->query("SHOW COLUMNS FROM favoritos LIKE 'fecha_agregado'");
    
    if ($check->rowCount() === 0) {
        // Add the column
        $pdo->exec("ALTER TABLE favoritos ADD COLUMN fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER producto_id");
        echo "<p style='color: green;'>✅ Added 'fecha_agregado' column to favoritos table.</p>";
    } else {
        echo "<p style='color: orange;'>ℹ️ 'fecha_agregado' column already exists.</p>";
    }
    
    // Show table structure
    echo "<h3>Current table structure:</h3>";
    $columns = $pdo->query("DESCRIBE favoritos")->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold;'>✅ Favorites table fixed! Try using the favorites functionality now.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Show connection details (without password)
    echo "<h3>Connection Details:</h3>";
    echo "<pre>Host: " . htmlspecialchars($dbConfig['host'] ?? 'not set') . "\nDatabase: " . htmlspecialchars($dbConfig['database'] ?? 'not set') . "\nUser: " . htmlspecialchars($dbConfig['username'] ?? 'not set') . "</pre>";
}

echo "<p><a href='check_db.php'>Check Database</a> | <a href='index.php'>Back to Site</a></p>";
