<?php
require_once __DIR__ . '/../models/Vendedor.php';
require_once __DIR__ . '/../helpers/UploadHelper.php';

class VendedorController {
    private $vendedorModel;
    private $uploadHelper;

    public function __construct() {
        $this->vendedorModel = new Vendedor();
        $this->uploadHelper = new UploadHelper([
            'upload_dir' => __DIR__ . '/../uploads/profiles/',
            'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
            'max_size' => 5 * 1024 * 1024, // 5MB
            'encrypt_name' => true
        ]);
    }

    /**
     * Show seller profile
     */
    public function mostrarPerfil() {
        if (!isLoggedIn() || !isVendedor()) {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        $perfil = $this->vendedorModel->obtenerPerfil($usuario_id);
        
        if (!$perfil) {
            // Redirect to profile setup if profile doesn't exist
            redirect('vendedor/perfil/editar');
        }

        // Get seller statistics
        $estadisticas = $this->vendedorModel->obtenerEstadisticas($usuario_id);
        
        // Render profile view
        $this->render('vendedor/perfil/mostrar', [
            'perfil' => $perfil,
            'estadisticas' => $estadisticas
        ]);
    }

    /**
     * Show profile edit form
     */
    public function editarPerfil() {
        if (!isLoggedIn() || !isVendedor()) {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        $perfil = $this->vendedorModel->obtenerPerfil($usuario_id);
        
        // If profile doesn't exist, initialize with empty data
        if (!$perfil) {
            $perfil = [
                'usuario_id' => $usuario_id,
                'nombre_tienda' => '',
                'rut_documento' => '',
                'email_empresa' => '',
                'descripcion_tienda' => '',
                'telefono_contacto' => '',
                'direccion_tienda' => '',
                'ciudad_tienda' => '',
                'pais_tienda' => '',
                'redes_sociales' => [
                    'facebook' => '',
                    'instagram' => '',
                    'twitter' => '',
                    'tiktok' => ''
                ]
            ];
        } else if (isset($perfil['redes_sociales']) && is_string($perfil['redes_sociales'])) {
            // Decode JSON string to array if needed
            $perfil['redes_sociales'] = json_decode($perfil['redes_sociales'], true);
        }

        $this->render('vendedor/perfil/editar', ['perfil' => $perfil]);
    }

    /**
     * Save profile data
     */
    public function guardarPerfil() {
        if (!isLoggedIn() || !isVendedor() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        
        // Validate input
        $errores = [];
        $datos = [
            'usuario_id' => $usuario_id,
            'nombre_tienda' => trim($_POST['nombre_tienda'] ?? ''),
            'rut_documento' => trim($_POST['rut_documento'] ?? ''),
            'email_empresa' => trim($_POST['email_empresa'] ?? ''),
            'descripcion_tienda' => trim($_POST['descripcion_tienda'] ?? ''),
            'telefono_contacto' => trim($_POST['telefono_contacto'] ?? ''),
            'direccion_tienda' => trim($_POST['direccion_tienda'] ?? ''),
            'ciudad_tienda' => trim($_POST['ciudad_tienda'] ?? ''),
            'pais_tienda' => trim($_POST['pais_tienda'] ?? ''),
            'redes_sociales' => [
                'facebook' => trim($_POST['redes_sociales']['facebook'] ?? ''),
                'instagram' => trim($_POST['redes_sociales']['instagram'] ?? ''),
                'twitter' => trim($_POST['redes_sociales']['twitter'] ?? ''),
                'tiktok' => trim($_POST['redes_sociales']['tiktok'] ?? '')
            ]
        ];

        // Validate required fields
        if (empty($datos['nombre_tienda'])) {
            $errores['nombre_tienda'] = 'El nombre de la tienda es obligatorio';
        }

        if (empty($datos['rut_documento'])) {
            $errores['rut_documento'] = 'El RUT o documento es obligatorio';
        }

        if (!filter_var($datos['email_empresa'], FILTER_VALIDATE_EMAIL)) {
            $errores['email_empresa'] = 'Ingrese un correo electrónico válido';
        }

        // Handle file upload if provided
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadResult = $this->uploadHelper->upload('foto_perfil');
                if ($uploadResult['success']) {
                    // Delete old profile picture if exists
                    $perfil_actual = $this->vendedorModel->obtenerPerfil($usuario_id);
                    if ($perfil_actual && !empty($perfil_actual['foto_perfil'])) {
                        $old_file = __DIR__ . '/..' . $perfil_actual['foto_perfil'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    
                    // Update profile picture path
                    $this->vendedorModel->actualizarFotoPerfil($usuario_id, $uploadResult['path']);
                } else {
                    $errores['foto_perfil'] = $uploadResult['error'];
                }
            } catch (Exception $e) {
                $errores['foto_perfil'] = 'Error al subir la imagen: ' . $e->getMessage();
            }
        }

        // Save profile if no errors
        if (empty($errores)) {
            if ($this->vendedorModel->guardarPerfil($datos)) {
                setFlashMessage('success', 'Perfil actualizado correctamente');
                redirect('vendedor/perfil');
            } else {
                $errores['general'] = 'Error al guardar el perfil. Por favor, intente nuevamente.';
            }
        }

        // If we get here, there were errors
        $this->render('vendedor/perfil/editar', [
            'perfil' => $datos,
            'errores' => $errores
        ]);
    }

    /**
     * Show seller dashboard
     */
    public function dashboard() {
        if (!isLoggedIn() || !isVendedor()) {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        $estadisticas = $this->vendedorModel->obtenerEstadisticas($usuario_id);
        
        // Get recent orders
        $pedidos = $this->vendedorModel->obtenerPedidos($usuario_id, [
            'por_pagina' => 5,
            'pagina' => 1
        ]);
        
        // Get low stock products
        $productos_bajo_stock = $this->vendedorModel->obtenerProductos($usuario_id, [
            'estado_stock' => 'bajo_stock',
            'por_pagina' => 5
        ]);

        $this->render('vendedor/dashboard', [
            'estadisticas' => $estadisticas,
            'pedidos_recientes' => $pedidos,
            'productos_bajo_stock' => $productos_bajo_stock
        ]);
    }

    /**
     * Show seller products
     */
    public function productos() {
        if (!isLoggedIn() || !isVendedor()) {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        $pagina = $_GET['pagina'] ?? 1;
        $por_pagina = 10;
        $filtros = [
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'estado_stock' => $_GET['estado_stock'] ?? null,
            'categoria_id' => $_GET['categoria_id'] ?? null,
            'busqueda' => $_GET['busqueda'] ?? null,
            'orden' => $_GET['orden'] ?? 'fecha_publicacion DESC'
        ];

        $productos = $this->vendedorModel->obtenerProductos($usuario_id, $filtros);
        
        // Get total count for pagination
        $total = $this->vendedorModel->contarProductos($usuario_id, $filtros);
        $total_paginas = ceil($total / $por_pagina);

        $this->render('vendedor/productos/lista', [
            'productos' => $productos,
            'pagina_actual' => $pagina,
            'total_paginas' => $total_paginas,
            'filtros' => $filtros
        ]);
    }

    /**
     * Show product form (create/edit)
     */
    public function formularioProducto($producto_id = null) {
        if (!isLoggedIn() || !isVendedor()) {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        $producto = null;
        $imagenes = [];
        
        if ($producto_id) {
            // Edit mode - load product data
            $producto = $this->vendedorModel->obtenerProducto($producto_id, $usuario_id);
            if (!$producto) {
                setFlashMessage('error', 'Producto no encontrado o no tienes permiso para editarlo');
                redirect('vendedor/productos');
            }
            
            // Load product images
            $imagenes = $this->vendedorModel->obtenerImagenesProducto($producto_id);
        }

        // Load categories for dropdown
        $categorias = $this->vendedorModel->obtenerCategorias();

        $this->render('vendedor/productos/formulario', [
            'producto' => $producto,
            'imagenes' => $imagenes,
            'categorias' => $categorias,
            'modo_edicion' => (bool)$producto_id
        ]);
    }

    /**
     * Save product (create/update)
     */
    public function guardarProducto() {
        if (!isLoggedIn() || !isVendedor() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        $producto_id = $_POST['producto_id'] ?? null;
        $modo_edicion = !empty($producto_id);
        
        // Validate input
        $errores = [];
        $datos = [
            'vendedor_id' => $usuario_id,
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => (float)($_POST['precio'] ?? 0),
            'precio_oferta' => !empty($_POST['precio_oferta']) ? (float)$_POST['precio_oferta'] : null,
            'stock' => (int)($_POST['stock'] ?? 0),
            'categoria_id' => (int)($_POST['categoria_id'] ?? 0),
            'condicion' => $_POST['condicion'] ?? 'nuevo',
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'etiquetas' => !empty($_POST['etiquetas']) ? explode(',', $_POST['etiquetas']) : [],
            'umbral_stock' => (int)($_POST['umbral_stock'] ?? 5)
        ];

        // Validate required fields
        if (empty($datos['nombre'])) {
            $errores['nombre'] = 'El nombre del producto es obligatorio';
        }

        if ($datos['precio'] <= 0) {
            $errores['precio'] = 'El precio debe ser mayor a 0';
        }

        if ($datos['categoria_id'] <= 0) {
            $errores['categoria_id'] = 'Seleccione una categoría válida';
        }

        // Handle file uploads
        $imagenes_subidas = [];
        if (!empty($_FILES['imagenes']['name'][0])) {
            try {
                $uploadResult = $this->uploadHelper->uploadMultiple('imagenes');
                if ($uploadResult['success']) {
                    $imagenes_subidas = $uploadResult['paths'];
                } else {
                    $errores['imagenes'] = $uploadResult['error'];
                }
            } catch (Exception $e) {
                $errores['imagenes'] = 'Error al subir las imágenes: ' . $e->getMessage();
            }
        } elseif (!$modo_edicion && empty($imagenes_subidas)) {
            // Require at least one image for new products
            $errores['imagenes'] = 'Debe subir al menos una imagen del producto';
        }

        // Save product if no errors
        if (empty($errores)) {
            try {
                if ($modo_edicion) {
                    // Update existing product
                    $datos['id'] = $producto_id;
                    $this->vendedorModel->actualizarProducto($datos);
                    $mensaje = 'Producto actualizado correctamente';
                } else {
                    // Create new product
                    $producto_id = $this->vendedorModel->crearProducto($datos);
                    $mensaje = 'Producto creado correctamente';
                }

                // Save uploaded images
                if (!empty($imagenes_subidas)) {
                    $this->vendedorModel->agregarImagenesProducto($producto_id, $imagenes_subidas);
                }

                // Handle image reordering
                if (!empty($_POST['imagen_orden'])) {
                    $this->vendedorModel->actualizarOrdenImagenes($producto_id, $_POST['imagen_orden']);
                }

                setFlashMessage('success', $mensaje);
                redirect('vendedor/productos/editar/' . $producto_id);
                
            } catch (Exception $e) {
                $errores['general'] = 'Error al guardar el producto: ' . $e->getMessage();
                error_log('Error al guardar producto: ' . $e->getMessage());
            }
        }

        // If we get here, there were errors
        $categorias = $this->vendedorModel->obtenerCategorias();
        
        $this->render('vendedor/productos/formulario', [
            'producto' => $datos,
            'categorias' => $categorias,
            'errores' => $errores,
            'modo_edicion' => $modo_edicion
        ]);
    }

    /**
     * Delete a product
     */
    public function eliminarProducto($producto_id) {
        if (!isLoggedIn() || !isVendedor()) {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        
        try {
            $this->vendedorModel->eliminarProducto($producto_id, $usuario_id);
            setFlashMessage('success', 'Producto eliminado correctamente');
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al eliminar el producto: ' . $e->getMessage());
        }
        
        redirect('vendedor/productos');
    }

    /**
     * Show seller orders
     */
    public function pedidos() {
        if (!isLoggedIn() || !isVendedor()) {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        $pagina = $_GET['pagina'] ?? 1;
        $por_pagina = 10;
        
        $filtros = [
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'estado' => $_GET['estado'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null
        ];

        $pedidos = $this->vendedorModel->obtenerPedidos($usuario_id, $filtros);
        
        // Get total count for pagination
        $total = $this->vendedorModel->contarPedidos($usuario_id, $filtros);
        $total_paginas = ceil($total / $por_pagina);

        $this->render('vendedor/pedidos/lista', [
            'pedidos' => $pedidos,
            'pagina_actual' => $pagina,
            'total_paginas' => $total_paginas,
            'filtros' => $filtros
        ]);
    }

    /**
     * Show order details
     */
    public function verPedido($pedido_id) {
        if (!isLoggedIn() || !isVendedor()) {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        
        // Get order details
        $pedido = $this->vendedorModel->obtenerPedido($pedido_id, $usuario_id);
        if (!$pedido) {
            setFlashMessage('error', 'Pedido no encontrado o no tienes permiso para verlo');
            redirect('vendedor/pedidos');
        }
        
        // Get order items
        $items = $this->vendedorModel->obtenerDetallePedido($pedido_id, $usuario_id);
        
        // Get status history
        $historial = $this->vendedorModel->obtenerHistorialPedido($pedido_id);

        $this->render('vendedor/pedidos/ver', [
            'pedido' => $pedido,
            'items' => $items,
            'historial' => $historial
        ]);
    }

    /**
     * Update order status
     */
    public function actualizarEstadoPedido($pedido_id) {
        if (!isLoggedIn() || !isVendedor() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        $nuevo_estado = $_POST['estado'] ?? '';
        $comentario = trim($_POST['comentario'] ?? '');
        
        try {
            $this->vendedorModel->actualizarEstadoPedido(
                $pedido_id, 
                $nuevo_estado, 
                $comentario, 
                $usuario_id
            );
            
            setFlashMessage('success', 'Estado del pedido actualizado correctamente');
        } catch (Exception $e) {
            setFlashMessage('error', 'Error al actualizar el estado del pedido: ' . $e->getMessage());
        }
        
        redirect('vendedor/pedidos/ver/' . $pedido_id);
    }

    /**
     * Report a product
     */
    public function reportarProducto() {
        if (!isLoggedIn() || !isVendedor() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        $usuario_id = $_SESSION['user_id'];
        $datos = [
            'producto_id' => (int)$_POST['producto_id'],
            'usuario_id' => $usuario_id,
            'motivo' => $_POST['motivo'] ?? 'otro',
            'descripcion' => trim($_POST['descripcion'] ?? '')
        ];

        // Validate input
        if (empty($datos['producto_id'])) {
            jsonResponse(['success' => false, 'message' => 'ID de producto no válido']);
            return;
        }

        if ($datos['motivo'] === 'otro' && empty($datos['descripcion'])) {
            jsonResponse(['success' => false, 'message' => 'Por favor, proporcione una descripción del problema']);
            return;
        }

        try {
            $this->vendedorModel->reportarProducto($datos);
            jsonResponse(['success' => true, 'message' => 'Producto reportado correctamente']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error al reportar el producto: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle product favorite status
     */
    public function toggleFavorito($producto_id) {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Debe iniciar sesión para guardar favoritos']);
            return;
        }

        $usuario_id = $_SESSION['user_id'];
        
        try {
            $resultado = $this->vendedorModel->toggleFavorito($usuario_id, $producto_id);
            jsonResponse([
                'success' => true, 
                'action' => $resultado,
                'message' => $resultado === 'added' ? 'Producto agregado a favoritos' : 'Producto eliminado de favoritos'
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Error al actualizar favoritos: ' . $e->getMessage()]);
        }
    }

    /**
     * Show favorites list
     */
    public function favoritos() {
        if (!isLoggedIn()) {
            redirect('login.php');
        }

        $usuario_id = $_SESSION['user_id'];
        $pagina = $_GET['pagina'] ?? 1;
        $por_pagina = 12;

        $favoritos = $this->vendedorModel->obtenerFavoritos($usuario_id, $pagina, $por_pagina);
        
        // Get total count for pagination
        $total = $this->vendedorModel->contarFavoritos($usuario_id);
        $total_paginas = ceil($total / $por_pagina);

        $this->render('vendedor/favoritos/lista', [
            'favoritos' => $favoritos,
            'pagina_actual' => $pagina,
            'total_paginas' => $total_paginas
        ]);
    }

    /**
     * Helper method to render views
     */
    private function render($view, $data = []) {
        extract($data);
        $viewPath = __DIR__ . "/../views/{$view}.php";
        
        if (!file_exists($viewPath)) {
            throw new Exception("View {$view} not found");
        }
        
        // Start output buffering
        ob_start();
        include $viewPath;
        $content = ob_get_clean();
        
        // Include layout
        include __DIR__ . '/../views/layouts/vendedor.php';
    }
}
