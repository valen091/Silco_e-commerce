<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Include database configuration and Database class
    require_once 'config/database.php';
    require_once 'includes/Database.php';
    
    // Get database connection using the Database singleton
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Include the migration class
    require_once 'database/migrations/20231204_create_favoritos_table.php';
    
    // Run the migration
    $migration = new CreateFavoritosTable();
    $migration->up($pdo);
    
    echo "Successfully created 'favoritos' table.\n";
    
    // Run the second migration to add fecha_agregado if it doesn't exist
    try {
        require_once 'database/migrations/20231209_add_fecha_agregado_to_favoritos.php';
        $migration2 = new AddFechaAgregadoToFavoritos();
        $migration2->up($pdo);
        echo "Successfully updated 'favoritos' table with 'fecha_agregado' column.\n";
    } catch (Exception $e) {
        echo "Note: Could not update 'favoritos' table with 'fecha_agregado' column. It might already exist.\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "Migration completed. Please check your database.\n";
?>
