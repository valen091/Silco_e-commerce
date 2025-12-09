<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Database.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test database connection
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: green;'>✅ Successfully connected to the database.</p>";
    
    // List all tables
    echo "<h3>Database Tables:</h3>";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p>No tables found in the database.</p>";
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table";
            
            // Show table structure
            $columns = $db->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1' style='margin: 10px 0 20px 20px; border-collapse: collapse;'>";
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
            
            // Show foreign keys
            $fks = $db->query("
                SELECT 
                    COLUMN_NAME, 
                    REFERENCED_TABLE_NAME, 
                    REFERENCED_COLUMN_NAME
                FROM 
                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE 
                    TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = '$table'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($fks)) {
                echo "<div style='margin-left: 20px;'>";
                echo "<strong>Foreign Keys:</strong><br>";
                foreach ($fks as $fk) {
                    echo "- {$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}({$fk['REFERENCED_COLUMN_NAME']})<br>";
                }
                echo "</div>";
            }
            
            echo "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Show connection details (without password)
    echo "<h3>Connection Details:</h3>";
    echo "<pre>Host: " . htmlspecialchars(DB_HOST) . "
Database: " . htmlspecialchars(DB_NAME) . "
User: " . htmlspecialchars(DB_USER) . "
Password: " . (defined('DB_PASS') ? '*****' : 'Not set') . "</pre>";
    
    // Try to connect directly with PDO to get more detailed error
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        echo "<p style='color: green;'>✅ Direct PDO connection successful!</p>";
    } catch (PDOException $pdoe) {
        echo "<p style='color: red;'>❌ Direct PDO connection failed: " . htmlspecialchars($pdoe->getMessage()) . "</p>";
    }
}

// Check if favoritos table exists
if (isset($db)) {
    echo "<h3>Checking 'favoritos' table:</h3>";
    $tableExists = $db->query("SHOW TABLES LIKE 'favoritos'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color: green;'>✅ 'favoritos' table exists.</p>";
        
        // Check if the user is logged in
        session_start();
        if (!empty($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            echo "<p>Current user ID from session: $userId</p>";
            
            // Try to get user from database
            $user = $db->query("SELECT id, email FROM usuarios WHERE id = " . (int)$userId)->fetch();
            if ($user) {
                echo "<p>User found in database: " . htmlspecialchars($user['email']) . " (ID: {$user['id']})</p>";
            } else {
                echo "<p style='color: red;'>❌ User with ID $userId not found in the database!</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No user is currently logged in.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ 'favoritos' table does not exist in the database.</p>";
    }
}

// Show PHP info
if (isset($_GET['phpinfo'])) {
    phpinfo();
    exit;
}

echo "<p><a href='?phpinfo=1'>Show PHP Info</a> | <a href='check_database.php'>Run Database Check</a></p>";
