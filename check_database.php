<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Database.php';

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    // Check if favoritos table exists
    $stmt = $db->query("SHOW TABLES LIKE 'favoritos'");
    if ($stmt->rowCount() === 0) {
        die("The 'favoritos' table does not exist in the database.\n");
    }
    
    // Get table structure
    $stmt = $db->query("DESCRIBE favoritos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table 'favoritos' structure:\n";
    echo str_pad("Field", 20) . str_pad("Type", 20) . str_pad("Null", 10) . str_pad("Key", 10) . "Default\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($columns as $column) {
        echo str_pad($column['Field'], 20) . 
             str_pad($column['Type'], 20) . 
             str_pad($column['Null'], 10) . 
             str_pad($column['Key'], 10) . 
             $column['Default'] . "\n";
    }
    
    // Check foreign key constraints
    $stmt = $db->query("
        SELECT 
            TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
            REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            REFERENCED_TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'favoritos'
    ");
    
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nForeign Key Constraints:\n";
    if (empty($constraints)) {
        echo "No foreign key constraints found for 'favoritos' table.\n";
    } else {
        foreach ($constraints as $constraint) {
            echo "- {$constraint['COLUMN_NAME']} references {$constraint['REFERENCED_TABLE_NAME']}({$constraint['REFERENCED_COLUMN_NAME']})\n";
        }
    }
    
    // Check if the product exists
    $productId = 1; // Default product ID to check
    if (isset($_GET['product_id'])) {
        $productId = (int)$_GET['product_id'];
    }
    
    echo "\nChecking if product with ID $productId exists...\n";
    $stmt = $db->prepare("SELECT id, nombre FROM productos WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "Product found: {$product['nombre']} (ID: {$product['id']})\n";
    } else {
        echo "Product with ID $productId not found in the database.\n";
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
