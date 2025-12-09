<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Database.php';

// Include the migration
require_once __DIR__ . '/database/migrations/20231209_add_fecha_agregado_to_favoritos.php';

echo "<h2>Fixing Favoritos Table</h2>";

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    // Check if favoritos table exists
    $tableExists = $db->query("SHOW TABLES LIKE 'favoritos'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("<p style='color: red;'>❌ The 'favoritos' table does not exist. Please create it first.</p>");
    }
    
    // Run the migration
    $migration = new AddFechaAgregadoToFavoritos();
    $migration->up($db);
    
    // Verify the column was added
    $columnExists = $db->query("SHOW COLUMNS FROM favoritos LIKE 'fecha_agregado'")->rowCount() > 0;
    
    if ($columnExists) {
        echo "<p style='color: green;'>✅ Successfully added 'fecha_agregado' column to the 'favoritos' table.</p>";
        
        // Show the table structure
        $columns = $db->query("DESCRIBE favoritos")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Updated 'favoritos' table structure:</h3>";
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
        
        echo "<p style='margin-top: 20px;'><strong>✅ The favorites functionality should now work correctly. Try adding a product to favorites again.</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to add 'fecha_agregado' column. Please check the error log for details.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Error fixing favoritos table: " . $e->getMessage());
    
    // Show more details for debugging
    if (strpos($e->getMessage(), 'Base table or view not found') !== false) {
        echo "<p>The 'favoritos' table doesn't exist. You'll need to create it first.</p>";
    }
}

echo "<p><a href='check_db.php'>Back to Database Check</a></p>";
