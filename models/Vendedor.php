<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/UploadHelper.php';

class Vendedor {
    private $db;
    private $table = 'perfiles_vendedor';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get seller profile by user ID
     */
    public function obtenerPerfil($usuario_id) {
        $conn = $this->db->getConnection();
        $query = "SELECT pv.*, u.nombre, u.apellido, u.email, u.telefono, u.direccion, u.ciudad, u.codigo_postal, u.pais 
                 FROM {$this->table} pv
                 JOIN usuarios u ON pv.usuario_id = u.id
                 WHERE pv.usuario_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$usuario_id]);
        
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Decode JSON fields
        if ($perfil && isset($perfil['redes_sociales'])) {
            $perfil['redes_sociales'] = json_decode($perfil['redes_sociales'], true) ?: [];
        }
        
        return $perfil;
    }
    
    /**
     * Get seller profile by store name
     */
    public function obtenerPerfilPorTienda($nombre_tienda) {
        $conn = $this->db->getConnection();
        $query = "SELECT pv.*, u.nombre, u.apellido, u.email 
                 FROM {$this->table} pv
                 JOIN usuarios u ON pv.usuario_id = u.id
                 WHERE pv.nombre_tienda = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$nombre_tienda]);
        
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($perfil && isset($perfil['redes_sociales'])) {
            $perfil['redes_sociales'] = json_decode($perfil['redes_sociales'], true) ?: [];
        }
        
        return $perfil;
    }

    /**
     * Create or update seller profile
     */
    public function guardarPerfil($datos) {
        $conn = $this->db->connect();
        
        // Check if profile exists
        $perfil = $this->obtenerPerfil($datos['usuario_id']);
        
        // Handle file upload if exists
        $foto_perfil = $perfil['foto_perfil'] ?? null;
        if (!empty($_FILES['foto_perfil']['name'])) {
            $uploadHelper = new UploadHelper('foto_perfil', 'perfiles');
            $uploadResult = $uploadHelper->upload();
            if ($uploadResult['success']) {
                $foto_perfil = $uploadResult['file_path'];
                // Delete old photo if exists
                if (!empty($perfil['foto_perfil']) && file_exists($perfil['foto_perfil'])) {
                    unlink($perfil['foto_perfil']);
                }
            }
        }
        
        if ($perfil) {
            // Update existing profile
            $query = "UPDATE {$this->table} SET 
                     nombre_tienda = ?, 
                     rut_documento = ?,
                     email_empresa = ?,
                     descripcion_tienda = ?,
                     telefono_contacto = ?,
                     direccion_tienda = ?,
                     ciudad_tienda = ?,
                     pais_tienda = ?,
                     redes_sociales = ?,
                     foto_perfil = ?,
                     fecha_actualizacion = NOW()
                     WHERE usuario_id = ?";
            
            $stmt = $conn->prepare($query);
            return $stmt->execute([
                $datos['nombre_tienda'],
                $datos['rut_documento'],
                $datos['email_empresa'],
                $datos['descripcion_tienda'],
                $datos['telefono_contacto'],
                $datos['direccion_tienda'],
                $datos['ciudad_tienda'],
                $datos['pais_tienda'],
                json_encode($datos['redes_sociales'] ?? []),
                $foto_perfil,
                $datos['usuario_id']
            ]);
        } else {
            // Create new profile
            $query = "INSERT INTO {$this->table} 
                     (usuario_id, nombre_tienda, rut_documento, email_empresa, 
                     descripcion_tienda, telefono_contacto, direccion_tienda, 
                     ciudad_tienda, pais_tienda, redes_sociales, foto_perfil) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            return $stmt->execute([
                $datos['usuario_id'],
                $datos['nombre_tienda'],
                $datos['rut_documento'],
                $datos['email_empresa'],
                $datos['descripcion_tienda'],
                $datos['telefono_contacto'],
                $datos['direccion_tienda'],
                $datos['ciudad_tienda'],
                $datos['pais_tienda'],
                json_encode($datos['redes_sociales'] ?? []),
                $foto_perfil
            ]);
        }
    }

    /**
     * Update seller profile picture
     */
    public function actualizarFotoPerfil($usuario_id, $foto_path) {
        $conn = $this->db->connect();
        
        // Get current photo to delete it later
        $current_photo = $conn->prepare("SELECT foto_perfil FROM {$this->table} WHERE usuario_id = ?");
        $current_photo->execute([$usuario_id]);
        $old_photo = $current_photo->fetchColumn();
        
        // Update with new photo
        $query = "UPDATE {$this->table} SET foto_perfil = ?, fecha_actualizacion = NOW() WHERE usuario_id = ?";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([$foto_path, $usuario_id]);
        
        // Delete old photo if exists and update was successful
        if ($result && $old_photo && file_exists($old_photo)) {
            unlink($old_photo);
        }
        
        return $result;
    }

    /**
     * Get seller products with advanced filtering and pagination
     */
    public function obtenerProductos($vendedor_id, $filtros = []) {
        $conn = $this->db->connect();
        
        $where = "WHERE p.vendedor_id = ? AND p.activo = 1";
        $params = [$vendedor_id];
        $join = "";
        
        // Apply filters
        if (!empty($filtros['categoria_id'])) {
            $where .= " AND p.categoria_id = ?";
            $params[] = $filtros['categoria_id'];
        }
        
        if (!empty($filtros['estado_stock'])) {
            switch ($filtros['estado_stock']) {
                case 'disponible':
                    $where .= " AND p.stock > 5";
                    break;
                case 'ultimo':
                    $where .= " AND p.stock = 1";
                    break;
                case 'agotado':
                    $where .= " AND p.stock = 0";
                    break;
                case 'bajo_stock':
                    $where .= " AND p.stock > 0 AND p.stock <= 5";
                    break;
            }
        }
        
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
            $search_term = "%{$filtros['busqueda']}%";
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($filtros['precio_min'])) {
            $where .= " AND p.precio >= ?";
            $params[] = $filtros['precio_min'];
        }
        
        if (!empty($filtros['precio_max'])) {
            $where .= " AND p.precio <= ?";
            $params[] = $filtros['precio_max'];
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $where .= " AND DATE(p.fecha_creacion) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $where .= " AND DATE(p.fecha_creacion) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        
        // Order by
        $order_by = "ORDER BY ";
        if (!empty($filtros['orden'])) {
            $order_parts = [];
            foreach ($filtros['orden'] as $field => $direction) {
                $order_parts[] = "p.{$field} {$direction}";
            }
            $order_by .= implode(", ", $order_parts);
        } else {
            $order_by .= "p.fecha_creacion DESC";
        }
        
        // Count total for pagination
        $count_query = "SELECT COUNT(DISTINCT p.id) as total 
                       FROM productos p
                       {$join}
                       {$where}";
        
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Pagination
        $limit = '';
        $current_page = $filtros['pagina'] ?? 1;
        $per_page = $filtros['por_pagina'] ?? 12;
        $offset = ($current_page - 1) * $per_page;
        
        $limit = "LIMIT {$offset}, {$per_page}";
        
        // Main query
        $query = "SELECT p.*, 
                 c.nombre as categoria_nombre,
                 (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id ORDER BY orden LIMIT 1) as imagen_principal,
                 (SELECT AVG(calificacion) FROM reseñas WHERE producto_id = p.id) as valoracion_promedio,
                 (SELECT COUNT(*) FROM reseñas WHERE producto_id = p.id) as total_valoraciones
                 FROM productos p
                 LEFT JOIN categorias c ON p.categoria_id = c.id
                 {$join}
                 {$where}
                 GROUP BY p.id
                 {$order_by}
                 {$limit}";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total pages
        $total_pages = ceil($total / $per_page);
        
        return [
            'productos' => $productos,
            'paginacion' => [
                'total' => $total,
                'por_pagina' => $per_page,
                'pagina_actual' => $current_page,
                'total_paginas' => $total_pages,
                'has_prev' => $current_page > 1,
                'has_next' => $current_page < $total_pages
            ]
        ];
    }

    /**
     * Get seller orders with advanced filtering and pagination
     */
    public function obtenerPedidos($vendedor_id, $filtros = []) {
        $conn = $this->db->connect();
        
        // Subquery to get only orders that have products from this seller
        $where = "WHERE p.id IN (SELECT DISTINCT ip.pedido_id FROM items_pedido ip 
                 JOIN productos pr ON ip.producto_id = pr.id 
                 WHERE pr.vendedor_id = ?)";
        $params = [$vendedor_id];
        
        // Apply filters
        if (!empty($filtros['estado'])) {
            $where .= " AND p.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['cliente'])) {
            $where .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ?)";
            $search_term = "%{$filtros['cliente']}%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $where .= " AND DATE(p.fecha_creacion) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $where .= " AND DATE(p.fecha_creacion) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        
        if (!empty($filtros['monto_min'])) {
            $where .= " AND (SELECT SUM(ip.cantidad * ip.precio_unitario) FROM items_pedido ip 
                           JOIN productos pr ON ip.producto_id = pr.id 
                           WHERE ip.pedido_id = p.id AND pr.vendedor_id = ?) >= ?";
            $params[] = $vendedor_id;
            $params[] = $filtros['monto_min'];
        }
        
        if (!empty($filtros['monto_max'])) {
            $where .= " AND (SELECT SUM(ip.cantidad * ip.precio_unitario) FROM items_pedido ip 
                           JOIN productos pr ON ip.producto_id = pr.id 
                           WHERE ip.pedido_id = p.id AND pr.vendedor_id = ?) <= ?";
            $params[] = $vendedor_id;
            $params[] = $filtros['monto_max'];
        }
        
        // Count total for pagination
        $count_query = "SELECT COUNT(DISTINCT p.id) as total 
                       FROM pedidos p
                       JOIN usuarios u ON p.usuario_id = u.id
                       {$where}";
        
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Order by
        $order_by = "ORDER BY ";
        if (!empty($filtros['orden'])) {
            $order_parts = [];
            foreach ($filtros['orden'] as $field => $direction) {
                $order_parts[] = "p.{$field} {$direction}";
            }
            $order_by .= implode(", ", $order_parts);
        } else {
            $order_by .= "p.fecha_creacion DESC";
        }
        
        // Pagination
        $current_page = $filtros['pagina'] ?? 1;
        $per_page = $filtros['por_pagina'] ?? 10;
        $offset = ($current_page - 1) * $per_page;
        
        $limit = "LIMIT {$offset}, {$per_page}";
        
        // Main query
        $query = "SELECT p.*, 
                 CONCAT(u.nombre, ' ', u.apellido) as nombre_cliente,
                 u.email as email_cliente,
                 u.telefono as telefono_cliente,
                 (SELECT COUNT(DISTINCT ip.id) 
                  FROM items_pedido ip 
                  JOIN productos pr ON ip.producto_id = pr.id 
                  WHERE ip.pedido_id = p.id AND pr.vendedor_id = ?) as total_items,
                 (SELECT SUM(ip.cantidad * ip.precio_unitario) 
                  FROM items_pedido ip 
                  JOIN productos pr ON ip.producto_id = pr.id 
                  WHERE ip.pedido_id = p.id AND pr.vendedor_id = ?) as total
                 FROM pedidos p
                 JOIN usuarios u ON p.usuario_id = u.id
                 {$where}
                 GROUP BY p.id
                 {$order_by}
                 {$limit}";
        
        // Add vendedor_id twice for the subqueries
        $params[] = $vendedor_id;
        $params[] = $vendedor_id;
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total pages
        $total_pages = ceil($total / $per_page);
        
        return [
            'pedidos' => $pedidos,
            'paginacion' => [
                'total' => $total,
                'por_pagina' => $per_page,
                'pagina_actual' => $current_page,
                'total_paginas' => $total_pages,
                'has_prev' => $current_page > 1,
                'has_next' => $current_page < $total_pages
            ]
        ];
    }

    /**
     * Get order details with seller validation
     */
    public function obtenerDetallePedido($pedido_id, $vendedor_id = null) {
        $conn = $this->db->connect();
        
        $where = "WHERE ip.pedido_id = ?";
        $params = [$pedido_id];
        
        // If vendedor_id is provided, only return items from that seller
        if ($vendedor_id) {
            $where .= " AND p.vendedor_id = ?";
            $params[] = $vendedor_id;
        }
        
        // Main query to get order items
        $query = "SELECT 
                    ip.*, 
                    p.nombre as producto_nombre, 
                    p.descripcion as producto_descripcion,
                    p.vendedor_id,
                    (SELECT nombre_tienda FROM perfiles_vendedor WHERE usuario_id = p.vendedor_id) as nombre_tienda,
                    (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id ORDER BY orden LIMIT 1) as imagen_producto,
                    pv.nombre as proveedor_nombre,
                    (SELECT estado FROM pedidos WHERE id = ip.pedido_id) as estado_pedido,
                    (SELECT fecha_creacion FROM pedidos WHERE id = ip.pedido_id) as fecha_pedido
                 FROM items_pedido ip
                 JOIN productos p ON ip.producto_id = p.id
                 LEFT JOIN proveedores pv ON p.proveedor_id = pv.id
                 {$where}";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            return [];
        }
        
        // Get order header information
        $order_query = "SELECT p.*, 
                       CONCAT(u.nombre, ' ', u.apellido) as nombre_cliente,
                       u.email as email_cliente,
                       u.telefono as telefono_cliente,
                       p.direccion_envio,
                       p.ciudad_envio,
                       p.codigo_postal_envio,
                       p.pais_envio,
                       p.metodo_pago,
                       p.estado as estado_pedido,
                       p.fecha_creacion as fecha_pedido
                       FROM pedidos p
                       JOIN usuarios u ON p.usuario_id = u.id
                       WHERE p.id = ?";
        
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->execute([$pedido_id]);
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate order totals for this seller's items
        $subtotal = 0;
        $envio = 0; // Could be calculated based on seller's shipping policy
        $impuestos = 0; // Could be calculated based on seller's location
        
        foreach ($items as $item) {
            $subtotal += $item['cantidad'] * $item['precio_unitario'];
        }
        
        // For simplicity, we'll use a flat rate for shipping and taxes
        // In a real application, these would be calculated based on the seller's settings
        $envio = 0; // Could be calculated based on weight, location, etc.
        $impuestos = $subtotal * 0.21; // 21% IVA as an example
        
        $total = $subtotal + $envio + $impuestos;
        
        return [
            'encabezado' => $order,
            'items' => $items,
            'totales' => [
                'subtotal' => $subtotal,
                'envio' => $envio,
                'impuestos' => $impuestos,
                'total' => $total
            ]
        ];
    }

    /**
     * Update order status with validation and history tracking
     */
    public function actualizarEstadoPedido($pedido_id, $nuevo_estado, $vendedor_id, $comentario = null) {
        $conn = $this->db->connect();
        
        try {
            $conn->beginTransaction();
            
            // Verify the order has items from this seller
            $verificacion = $conn->prepare(
                "SELECT DISTINCT p.id 
                FROM pedidos p
                JOIN items_pedido ip ON p.id = ip.pedido_id 
                JOIN productos pr ON ip.producto_id = pr.id 
                WHERE p.id = ? AND pr.vendedor_id = ?"
            );
            $verificacion->execute([$pedido_id, $vendedor_id]);
            
            if ($verificacion->rowCount() === 0) {
                $conn->rollBack();
                return [
                    'success' => false,
                    'message' => 'El pedido no contiene productos de este vendedor.'
                ];
            }
            
            // Get current status
            $stmt = $conn->prepare("SELECT estado FROM pedidos WHERE id = ?");
            $stmt->execute([$pedido_id]);
            $estado_actual = $stmt->fetchColumn();
            
            // Validate status transition
            $estados_validos = [
                'pendiente' => ['procesando', 'cancelado'],
                'procesando' => ['enviado', 'cancelado'],
                'enviado' => ['entregado', 'devuelto'],
                'entregado' => ['devuelto'],
                'cancelado' => [],
                'devuelto' => []
            ];
            
            if (!in_array($nuevo_estado, $estados_validos[$estado_actual] ?? [])) {
                $conn->rollBack();
                return [
                    'success' => false,
                    'message' => 'Transición de estado no permitida.'
                ];
            }
            
            // Update order status
            $query = "UPDATE pedidos SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?";
            $stmt = $conn->prepare($query);
            $update_result = $stmt->execute([$nuevo_estado, $pedido_id]);
            
            if (!$update_result) {
                throw new Exception("Error al actualizar el estado del pedido.");
            }
            
            // Log status change
            $stmt = $conn->prepare("INSERT INTO historial_estados_pedido 
                (pedido_id, vendedor_id, estado_anterior, estado_nuevo, comentario)
                VALUES (?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $pedido_id,
                $estado_actual,
                $nuevo_estado,
                $comentario,
                $usuario_id
            ]);
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Error al actualizar estado del pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Report a product with validation
     */
    public function reportarProducto($datos) {
        $conn = $this->db->connect();
        
        // Validate required fields
        $required = ['producto_id', 'usuario_id', 'motivo'];
        foreach ($required as $field) {
            if (empty($datos[$field])) {
                return [
                    'success' => false,
                    'message' => "El campo '{$field}' es obligatorio."
                ];
            }
        }
        
        // Check if user has already reported this product
        $check = $conn->prepare(
            "SELECT id FROM reportes_productos 
             WHERE producto_id = ? AND usuario_id = ? AND resuelto = 0"
        );
        $check->execute([$datos['producto_id'], $datos['usuario_id']]);
        
        if ($check->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'Ya has reportado este producto anteriormente.'
            ];
        }
        
        // Insert report
        $query = "INSERT INTO reportes_productos 
                 (producto_id, usuario_id, motivo, descripcion, fecha_reporte)
                 VALUES (?, ?, ?, ?, NOW())";
        
        try {
            $conn->beginTransaction();
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                $datos['producto_id'],
                $datos['usuario_id'],
                $datos['motivo'],
                $datos['descripcion'] ?? null
            ]);
            
            if (!$result) {
                throw new Exception("Error al registrar el reporte.");
            }
            
            $reporte_id = $conn->lastInsertId();
            
            // If the product has multiple reports, consider flagging it for review
            $count = $conn->prepare(
                "SELECT COUNT(*) as total FROM reportes_productos 
                 WHERE producto_id = ? AND resuelto = 0"
            );
            $count->execute([$datos['producto_id']]);
            $reportes = $count->fetch(PDO::FETCH_ASSOC)['total'];
            
            // If more than 5 reports, flag the product
            if ($reportes >= 5) {
                $update = $conn->prepare(
                    "UPDATE productos 
                     SET necesita_revision = 1, 
                         fecha_actualizacion = NOW()
                     WHERE id = ?"
                );
                $update->execute([$datos['producto_id']]);
            }
            
            $conn->commit();
            
            return [
                'success' => true,
                'message' => 'Reporte registrado correctamente.',
                'reporte_id' => $reporte_id
            ];
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Error en reportarProducto: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al registrar el reporte: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get seller statistics for dashboard
     */
    public function obtenerEstadisticas($vendedor_id) {
        $conn = $this->db->connect();
        
        $stats = [
            'total_productos' => 0,
            'total_ventas' => 0,
            'ingresos_totales' => 0,
            'pedidos_pendientes' => 0,
            'valoracion_promedio' => 0,
            'productos_bajo_stock' => 0,
            'ventas_mes_actual' => 0,
            'ingresos_mes_actual' => 0,
            'ventas_mes_anterior' => 0,
            'ingresos_mes_anterior' => 0,
            'tasa_crecimiento' => 0
        ];
        
        // Total products
        $query = "SELECT COUNT(*) as total FROM productos WHERE vendedor_id = ? AND activo = 1";
        $stmt = $conn->prepare($query);
        $stmt->execute([$vendedor_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_productos'] = (int)$result['total'];
        
        // Total sales and revenue (all time)
        $query = "SELECT 
                    COUNT(DISTINCT p.id) as total_ventas,
                    COALESCE(SUM(ip.cantidad * ip.precio_unitario), 0) as ingresos_totals
                  FROM pedidos p
                  JOIN items_pedido ip ON p.id = ip.pedido_id
                  JOIN productos pr ON ip.producto_id = pr.id
                  WHERE pr.vendedor_id = ? 
                  AND p.estado NOT IN ('cancelado', 'reembolsado')";
        $stmt = $conn->prepare($query);
        $stmt->execute([$vendedor_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['total_ventas'] = (int)$result['total_ventas'];
        $stats['ingresos_totales'] = (float)$result['ingresos_totals'];
        
        // Pending orders
        $query = "SELECT COUNT(DISTINCT p.id) as total
                  FROM pedidos p
                  JOIN items_pedido ip ON p.id = ip.pedido_id
                  JOIN productos pr ON ip.producto_id = pr.id
                  WHERE pr.vendedor_id = ? 
                  AND p.estado = 'pendiente'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$vendedor_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['pedidos_pendientes'] = (int)$result['total'];
        
        // Products with low stock
        $query = "SELECT COUNT(*) as total
                  FROM productos 
                  WHERE vendedor_id = ? 
                  AND stock > 0 
                  AND stock <= 5";
        $stmt = $conn->prepare($query);
        $stmt->execute([$vendedor_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['productos_bajo_stock'] = (int)$result['total'];
        
        // Average rating
        $query = "SELECT AVG(calificacion) as promedio, COUNT(*) as total_resenas
                  FROM reseñas r
                  JOIN productos p ON r.producto_id = p.id
                  WHERE p.vendedor_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$vendedor_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['valoracion_promedio'] = $result['promedio'] ? round($result['promedio'], 1) : 0;
        $stats['total_resenas'] = (int)$result['total_resenas'];
        
        // Current month sales and revenue
        $current_month = date('Y-m-01');
        $query = "SELECT 
                    COUNT(DISTINCT p.id) as ventas_mes_actual,
                    COALESCE(SUM(ip.cantidad * ip.precio_unitario), 0) as ingresos_mes_actual
                  FROM pedidos p
                  JOIN items_pedido ip ON p.id = ip.pedido_id
                  JOIN productos pr ON ip.producto_id = pr.id
                  WHERE pr.vendedor_id = ? 
                  AND p.estado NOT IN ('cancelado', 'reembolsado')
                  AND p.fecha_creacion >= ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$vendedor_id, $current_month]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['ventas_mes_actual'] = (int)$current['ventas_mes_actual'];
        $stats['ingresos_mes_actual'] = (float)$current['ingresos_mes_actual'];
        
        // Previous month sales and revenue for comparison
        $last_month = date('Y-m-01', strtotime('-1 month'));
        $query = "SELECT 
                    COUNT(DISTINCT p.id) as ventas_mes_anterior,
                    COALESCE(SUM(ip.cantidad * ip.precio_unitario), 0) as ingresos_mes_anterior
                  FROM pedidos p
                  JOIN items_pedido ip ON p.id = ip.pedido_id
                  JOIN productos pr ON ip.producto_id = pr.id
                  WHERE pr.vendedor_id = ? 
                  AND p.estado NOT IN ('cancelado', 'reembolsado')
                  AND p.fecha_creacion >= ?
                  AND p.fecha_creacion < ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$vendedor_id, $last_month, $current_month]);
        $previous = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['ventas_mes_anterior'] = (int)$previous['ventas_mes_anterior'];
        $stats['ingresos_mes_anterior'] = (float)$previous['ingresos_mes_anterior'];
        
        // Calculate growth rate
        if ($stats['ingresos_mes_anterior'] > 0) {
            $stats['tasa_crecimiento'] = round((
                ($stats['ingresos_mes_actual'] - $stats['ingresos_mes_anterior']) / 
                $stats['ingresos_mes_anterior']
            ) * 100, 1);
        } elseif ($stats['ingresos_mes_actual'] > 0) {
            $stats['tasa_crecimiento'] = 100; // Infinite growth (from 0 to positive)
        }
        
        // Get sales by category for the chart
        $query = "SELECT 
                    c.nombre as categoria,
                    COUNT(DISTINCT p.id) as total_ventas,
                    SUM(ip.cantidad) as total_unidades,
                    SUM(ip.cantidad * ip.precio_unitario) as ingresos
                  FROM pedidos p
                  JOIN items_pedido ip ON p.id = ip.pedido_id
                  JOIN productos pr ON ip.producto_id = pr.id
                  JOIN categorias c ON pr.categoria_id = c.id
                  WHERE pr.vendedor_id = ? 
                  AND p.estado NOT IN ('cancelado', 'reembolsado')
                  AND p.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                  GROUP BY c.id, c.nombre
                  ORDER BY ingresos DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$vendedor_id]);
        $stats['ventas_por_categoria'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    /**
     * Get recent sales for dashboard
     */
    public function obtenerVentasRecientes($vendedor_id, $limite = 5) {
        $conn = $this->db->connect();
        
        $query = "SELECT 
                    p.id as pedido_id,
                    p.estado,
                    p.fecha_creacion,
                    CONCAT(u.nombre, ' ', u.apellido) as cliente,
                    u.email as email_cliente,
                    COUNT(DISTINCT ip.id) as total_items,
                    SUM(ip.cantidad * ip.precio_unitario) as total
                  FROM pedidos p
                  JOIN items_pedido ip ON p.id = ip.pedido_id
                  JOIN productos pr ON ip.producto_id = pr.id
                  JOIN usuarios u ON p.usuario_id = u.id
                  WHERE pr.vendedor_id = ?
                  AND p.estado NOT IN ('cancelado', 'reembolsado')
                  GROUP BY p.id, p.estado, p.fecha_creacion, cliente, email_cliente
                  ORDER BY p.fecha_creacion DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$vendedor_id, $limite]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get sales data for charts
     */
    public function obtenerDatosGraficos($vendedor_id, $rango = '30d') {
        $conn = $this->db->connect();
        
        // Determine date range
        $now = new DateTime();
        $start_date = clone $now;
        
        switch ($rango) {
            case '7d':
                $start_date->modify('-7 days');
                $group_by = 'DATE_FORMAT(p.fecha_creacion, "%Y-%m-%d")';
                $interval = '1 DAY';
                $date_format = 'M j';
                break;
                
            case '30d':
            default:
                $start_date->modify('-30 days');
                $group_by = 'DATE_FORMAT(p.fecha_creacion, "%Y-%m-%d")';
                $interval = '1 DAY';
                $date_format = 'M j';
                break;
                
            case '90d':
                $start_date->modify('-90 days');
                $group_by = 'YEARWEEK(p.fecha_creacion, 1)';
                $interval = '1 WEEK';
                $date_format = 'M j';
                break;
                
            case '12m':
                $start_date->modify('-12 months');
                $group_by = 'DATE_FORMAT(p.fecha_creacion, "%Y-%m")';
                $interval = '1 MONTH';
                $date_format = 'M Y';
                break;
        }
        
        $start_date_str = $start_date->format('Y-m-d H:i:s');
        
        // Get sales data
        $query = "SELECT 
                    {$group_by} as fecha,
                    COUNT(DISTINCT p.id) as total_pedidos,
                    SUM(ip.cantidad) as total_unidades,
                    SUM(ip.cantidad * ip.precio_unitario) as ingresos
                  FROM pedidos p
                  JOIN items_pedido ip ON p.id = ip.pedido_id
                  JOIN productos pr ON ip.producto_id = pr.id
                  WHERE pr.vendedor_id = ?
                  AND p.estado NOT IN ('cancelado', 'reembolsado')
                  AND p.fecha_creacion >= ?
                  GROUP BY {$group_by}
                  ORDER BY fecha ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$vendedor_id, $start_date_str]);
        $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart
        $labels = [];
        $pedidos = [];
        $unidades = [];
        $ingresos = [];
        
        // Create a date range to fill in missing dates
        $period = new DatePeriod(
            new DateTime($start_date_str),
            new DateInterval("P{$interval}"),
            new DateTime()
        );
        
        // Initialize data arrays with zeros for all dates in range
        foreach ($period as $date) {
            $key = '';
            
            switch ($rango) {
                case '7d':
                case '30d':
                    $key = $date->format('Y-m-d');
                    $labels[] = $date->format($date_format);
                    break;
                    
                case '90d':
                    $week = $date->format('oW');
                    $key = $week;
                    $labels[] = 'Sem ' . $date->format('W');
                    break;
                    
                case '12m':
                    $key = $date->format('Y-m');
                    $labels[] = $date->format($date_format);
                    break;
            }
            
            $pedidos[$key] = 0;
            $unidades[$key] = 0;
            $ingresos[$key] = 0;
        }
        
        // Fill in actual data
        foreach ($sales_data as $row) {
            $key = $row['fecha'];
            
            if (isset($pedidos[$key])) {
                $pedidos[$key] = (int)$row['total_pedidos'];
                $unidades[$key] = (int)$row['total_unidades'];
                $ingresos[$key] = (float)$row['ingresos'];
            }
        }
        
        // Get top selling products
        $query = "SELECT 
                    pr.id,
                    pr.nombre,
                    pr.precio,
                    (SELECT imagen_url FROM imagenes_producto WHERE producto_id = pr.id ORDER BY orden LIMIT 1) as imagen,
                    SUM(ip.cantidad) as total_vendido,
                    SUM(ip.cantidad * ip.precio_unitario) as ingresos_totales
                  FROM productos pr
                  JOIN items_pedido ip ON pr.id = ip.producto_id
                  JOIN pedidos p ON ip.pedido_id = p.id
                  WHERE pr.vendedor_id = ?
                  AND p.estado NOT IN ('cancelado', 'reembolsado')
                  AND p.fecha_creacion >= ?
                  GROUP BY pr.id, pr.nombre, pr.precio
                  ORDER BY total_vendido DESC
                  LIMIT 5";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$vendedor_id, $start_date_str]);
        $top_productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'labels' => $labels,
            'datasets' => [
                'pedidos' => array_values($pedidos),
                'unidades' => array_values($unidades),
                'ingresos' => array_values($ingresos)
            ],
            'top_productos' => $top_productos
        ];
    }

    /**
     * Add/remove product from favorites
     * @param int $usuario_id User ID
     * @param int $producto_id Product ID
     * @return string|bool 'added' if added to favorites, 'removed' if removed, false on error
     */
    public function toggleFavorito($usuario_id, $producto_id) {
        $conn = $this->db->connect();
        
        try {
            // Check if already favorited
            $stmt = $conn->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND producto_id = ?");
            $stmt->execute([$usuario_id, $producto_id]);
            
            if ($stmt->fetch()) {
                // Remove from favorites
                $stmt = $conn->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND producto_id = ?");
                $result = $stmt->execute([$usuario_id, $producto_id]);
                return $result ? 'removed' : false;
            } else {
                // Add to favorites
                $stmt = $conn->prepare("INSERT INTO favoritos (usuario_id, producto_id, fecha_agregado) VALUES (?, ?, NOW())");
                $result = $stmt->execute([$usuario_id, $producto_id]);
                return $result ? 'added' : false;
            }
        } catch (PDOException $e) {
            error_log("Error en toggleFavorito: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's favorite products
     */
    public function obtenerFavoritos($usuario_id, $pagina = 1, $por_pagina = 10) {
        $conn = $this->db->connect();
        
        $offset = ($pagina - 1) * $por_pagina;
        
        $query = "SELECT p.*, 
                 (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id ORDER BY orden LIMIT 1) as imagen_principal,
                 c.nombre as categoria_nombre
                 FROM productos p
                 JOIN favoritos f ON p.id = f.producto_id
                 LEFT JOIN categorias c ON p.categoria_id = c.id
                 WHERE f.usuario_id = ?
                 ORDER BY f.fecha_agregado DESC
                 LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$usuario_id, $por_pagina, $offset]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
