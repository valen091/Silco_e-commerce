<?php

use App\Controllers\VendedorController;
use App\Controllers\ContactoController;

// Seller dashboard
$router->get('vendedor/panel', [VendedorController::class, 'dashboard']);

// Seller profile routes
$router->get('vendedor/perfil', [VendedorController::class, 'mostrarPerfil']);
$router->get('vendedor/perfil/editar', [VendedorController::class, 'editarPerfil']);
$router->post('vendedor/perfil/guardar', [VendedorController::class, 'guardarPerfil']);

// Products management
$router->get('vendedor/productos', [VendedorController::class, 'productos']);
$router->get('vendedor/productos/nuevo', [VendedorController::class, 'formularioProducto']);
$router->get('vendedor/productos/editar/{id}', [VendedorController::class, 'formularioProducto']);
$router->post('vendedor/productos/guardar', [VendedorController::class, 'guardarProducto']);
$router->post('vendedor/productos/eliminar/{id}', [VendedorController::class, 'eliminarProducto']);

// Orders management
$router->get('vendedor/pedidos', [VendedorController::class, 'pedidos']);
$router->get('vendedor/pedidos/ver/{id}', [VendedorController::class, 'verPedido']);
$router->post('vendedor/pedidos/actualizar-estado/{id}', [VendedorController::class, 'actualizarEstadoPedido']);

// Favorites
$router->get('vendedor/favoritos', [VendedorController::class, 'favoritos']);
$router->post('vendedor/favoritos/toggle/{producto_id}', [VendedorController::class, 'toggleFavorito']);

// Product reports
$router->post('vendedor/productos/reportar', [VendedorController::class, 'reportarProducto']);

// Contact routes
$router->get('contacto', [ContactoController::class, 'index']);
$router->post('contacto/enviar', [ContactoController::class, 'enviarMensaje']);
