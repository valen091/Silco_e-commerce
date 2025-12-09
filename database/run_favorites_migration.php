<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';

// Include the favorites migration
require_once __DIR__ . '/migrations/20231204_create_favoritos_table.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create an instance of the migration
    $migration = new CreateFavoritosTable();
    
    // Run the migration
    $migration->up($conn);
    
    echo "¡La tabla 'favoritos' se ha creado exitosamente!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($e instanceof PDOException) {
        echo "Error de base de datos: " . $e->getMessage() . "\n";
    }
}
