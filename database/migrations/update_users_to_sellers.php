<?php
// Cargar configuración de la base de datos
require_once __DIR__ . '/../../config/database.php';

$database = new Database();

try {
    // Conectar a la base de datos
    $conn = $database->connect();
    
    // Seleccionar la base de datos
    $dbName = getenv('DB_DATABASE');
    $conn->exec("USE `$dbName`");

    echo "Conectado a la base de datos: $dbName\n";
    
    // Verificar si la tabla usuarios existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'usuarios'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("Error: La tabla 'usuarios' no existe en la base de datos.\n");
    }

    // Verificar si la columna es_vendedor existe
    $columnExists = false;
    $stmt = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'es_vendedor'");
    $columnExists = $stmt->rowCount() > 0;

    if (!$columnExists) {
        // Agregar la columna si no existe
        echo "Agregando columna 'es_vendedor' a la tabla 'usuarios'...\n";
        $conn->exec("ALTER TABLE usuarios ADD COLUMN es_vendedor BOOLEAN DEFAULT TRUE");
        echo "Columna 'es_vendedor' agregada con éxito.\n";
    }

    // Actualizar todos los usuarios existentes a vendedores
    echo "Actualizando usuarios existentes...\n";
    $stmt = $conn->prepare("UPDATE usuarios SET es_vendedor = 1 WHERE es_vendedor = 0 OR es_vendedor IS NULL");
    $stmt->execute();
    $updated = $stmt->rowCount();

    // Modificar la columna para que por defecto los nuevos usuarios sean vendedores
    echo "Actualizando configuración por defecto para nuevos usuarios...\n";
    $conn->exec("ALTER TABLE usuarios MODIFY COLUMN es_vendedor BOOLEAN DEFAULT TRUE");

    echo "\n¡Migración completada con éxito!\n";
    echo "- Usuarios actualizados: $updated\n";
    echo "- Todos los usuarios ahora son vendedores por defecto.\n";

} catch (PDOException $e) {
    die("\nError en la migración: " . $e->getMessage() . "\n");
}

$conn = null; // Cerrar conexión
?>
