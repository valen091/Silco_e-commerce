<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';

// Incluir la migración
require_once __DIR__ . '/migrations/20231204_add_peso_dimensiones_to_productos.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Crear una instancia de la migración
    $migration = new AddPesoDimensionesToProductos();
    
    // Ejecutar la migración
    $result = $migration->up($conn);
    
    if ($result) {
        echo "¡Migración aplicada exitosamente!\n";
    } else {
        echo "Error al aplicar la migración. Verifica los logs para más detalles.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
