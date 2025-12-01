<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'silco_db';
$db_user = 'root';
$db_pass = 'silco';

try {
    // Create PDO instance
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Log the error and show a friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Error de conexión con la base de datos. Por favor, intente más tarde.");
}
?>
