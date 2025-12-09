<?php
// Start session
session_name('silco_session');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['count' => 0]);
        exit;
    }

    // Include database connection
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../includes/Database.php';

    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get favorites count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM favoritos WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => (int)$result['count']]);
    
} catch (Exception $e) {
    error_log('Error in count.php: ' . $e->getMessage());
    echo json_encode(['count' => 0]);
}