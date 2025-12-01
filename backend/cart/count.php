<?php
// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'use_strict_mode' => true,
        'use_cookies' => 1,
        'cookie_httponly' => 1,
        'cookie_samesite' => 'Lax'
    ]);
}

// Initialize cart count
$cartCount = 0;

// Check if cart exists in session
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = count($_SESSION['cart']);
}

// Return JSON response
echo json_encode([
    'success' => true,
    'count' => $cartCount
]);

exit();
