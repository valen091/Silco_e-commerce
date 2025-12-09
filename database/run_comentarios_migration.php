<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';

// Include the migration
require_once __DIR__ . '/migrations/20231209_create_comentarios_table.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create an instance of the migration
    $migration = new CreateComentariosTable();
    
    echo "Applying migration to create 'comentarios' table...\n";
    $migration->up($conn);
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "SQL Error: " . json_encode($e->errorInfo, JSON_PRETTY_PRINT) . "\n";
    }
    exit(1);
}
