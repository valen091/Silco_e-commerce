<?php

use PDO;

try {
    $db = new PDO(
        "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_DATABASE'),
        getenv('DB_USER'),
        getenv('DB_PASSWORD'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Enable foreign key constraints
    $db->exec("SET FOREIGN_KEY_CHECKS=0");

    // Create categories table
    $db->exec("CREATE TABLE IF NOT EXISTS categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        descripcion TEXT,
        imagen VARCHAR(255),
        estado TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create seller profiles table
    $db->exec("CREATE TABLE IF NOT EXISTS vendedor_perfiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        nombre_tienda VARCHAR(100) NOT NULL,
        slug_tienda VARCHAR(100) NOT NULL,
        descripcion TEXT,
        telefono VARCHAR(20),
        direccion TEXT,
        ciudad VARCHAR(100),
        departamento VARCHAR(100),
        pais VARCHAR(100) DEFAULT 'Uruguay',
        rut VARCHAR(20) UNIQUE,
        logo VARCHAR(255),
        portada VARCHAR(255),
        calificacion_promedio DECIMAL(3,2) DEFAULT 0.00,
        total_ventas INT DEFAULT 0,
        estado TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        UNIQUE KEY (slug_tienda)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create products table
    $db->exec("CREATE TABLE IF NOT EXISTS productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vendedor_id INT NOT NULL,
        categoria_id INT NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        descripcion TEXT,
        precio DECIMAL(10,2) NOT NULL,
        precio_descuento DECIMAL(10,2) DEFAULT NULL,
        stock INT NOT NULL DEFAULT 0,
        estado_stock ENUM('disponible', 'ultimo', 'agotado') DEFAULT 'disponible',
        estado TINYINT(1) DEFAULT 1,
        visitas INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (vendedor_id) REFERENCES vendedor_perfiles(id) ON DELETE CASCADE,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id),
        UNIQUE KEY (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create product images table
    $db->exec("CREATE TABLE IF NOT EXISTS producto_imagenes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL,
        imagen VARCHAR(255) NOT NULL,
        orden INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create orders table
    $db->exec("CREATE TABLE IF NOT EXISTS pedidos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero_pedido VARCHAR(20) NOT NULL,
        comprador_id INT NOT NULL,
        vendedor_id INT NOT NULL,
        direccion_envio TEXT NOT NULL,
        ciudad_envio VARCHAR(100) NOT NULL,
        departamento_envio VARCHAR(100) NOT NULL,
        codigo_postal VARCHAR(20),
        telefono_contacto VARCHAR(20) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        envio DECIMAL(10,2) DEFAULT 0.00,
        impuestos DECIMAL(10,2) DEFAULT 0.00,
        descuento DECIMAL(10,2) DEFAULT 0.00,
        total DECIMAL(10,2) NOT NULL,
        metodo_pago VARCHAR(50) NOT NULL,
        estado_pago ENUM('pendiente', 'procesando', 'pagado', 'reembolsado', 'cancelado') DEFAULT 'pendiente',
        estado_orden ENUM('nuevo', 'procesando', 'enviado', 'entregado', 'cancelado') DEFAULT 'nuevo',
        notas TEXT,
        paypal_order_id VARCHAR(100),
        paypal_payer_id VARCHAR(100),
        paypal_payment_id VARCHAR(100),
        paypal_payment_status VARCHAR(50),
        paypal_payment_email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (comprador_id) REFERENCES usuarios(id),
        FOREIGN KEY (vendedor_id) REFERENCES vendedor_perfiles(id),
        UNIQUE KEY (numero_pedido)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create order items table
    $db->exec("CREATE TABLE IF NOT EXISTS pedido_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pedido_id INT NOT NULL,
        producto_id INT NOT NULL,
        vendedor_id INT NOT NULL,
        precio_unitario DECIMAL(10,2) NOT NULL,
        cantidad INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        impuestos DECIMAL(10,2) DEFAULT 0.00,
        total DECIMAL(10,2) NOT NULL,
        devuelto TINYINT(1) DEFAULT 0,
        motivo_devolucion TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES productos(id),
        FOREIGN KEY (vendedor_id) REFERENCES vendedor_perfiles(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create favorites table
    $db->exec("CREATE TABLE IF NOT EXISTS favoritos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        producto_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
        UNIQUE KEY (usuario_id, producto_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create shopping cart table
    $db->exec("CREATE TABLE IF NOT EXISTS carrito (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        producto_id INT NOT NULL,
        cantidad INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
        UNIQUE KEY (usuario_id, producto_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create payment transactions table
    $db->exec("CREATE TABLE IF NOT EXISTS transacciones_pago (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pedido_id INT,
        vendedor_id INT NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        comision_plataforma DECIMAL(10,2) DEFAULT 0.00,
        monto_neto DECIMAL(10,2) NOT NULL,
        metodo_pago VARCHAR(50) NOT NULL,
        estado ENUM('pendiente', 'completado', 'fallido', 'reembolsado') DEFAULT 'pendiente',
        datos_transaccion TEXT,
        fecha_pago TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL,
        FOREIGN KEY (vendedor_id) REFERENCES vendedor_perfiles(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create seller notifications table
    $db->exec("CREATE TABLE IF NOT EXISTS notificaciones_vendedor (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vendedor_id INT NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        mensaje TEXT NOT NULL,
        tipo ENUM('pedido', 'pago', 'soporte', 'sistema') NOT NULL,
        enlace VARCHAR(255),
        leida TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vendedor_id) REFERENCES vendedor_perfiles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create support tickets table
    $db->exec("CREATE TABLE IF NOT EXISTS tickets_soporte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        vendedor_id INT,
        asunto VARCHAR(255) NOT NULL,
        estado ENUM('abierto', 'en_proceso', 'cerrado') DEFAULT 'abierto',
        prioridad ENUM('baja', 'media', 'alta') DEFAULT 'media',
        ultima_respuesta TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        FOREIGN KEY (vendedor_id) REFERENCES vendedor_perfiles(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create support messages table
    $db->exec("CREATE TABLE IF NOT EXISTS mensajes_soporte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        usuario_id INT NOT NULL,
        mensaje TEXT NOT NULL,
        adjunto VARCHAR(255),
        es_interno TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES tickets_soporte(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Add seller role to users table if not exists
    $db->exec("ALTER TABLE usuarios 
        ADD COLUMN IF NOT EXISTS es_vendedor TINYINT(1) DEFAULT 0 AFTER password,
        ADD COLUMN IF NOT EXISTS email_verificado TINYINT(1) DEFAULT 0 AFTER es_vendedor,
        ADD COLUMN IF NOT EXISTS telefono_verificado TINYINT(1) DEFAULT 0 AFTER email_verificado,
        ADD COLUMN IF NOT EXISTS ultimo_acceso TIMESTAMP NULL AFTER telefono_verificado,
        ADD COLUMN IF NOT EXISTS intentos_login INT DEFAULT 0 AFTER ultimo_acceso,
        ADD COLUMN IF NOT EXISTS bloqueado_hasta TIMESTAMP NULL AFTER intentos_login");

    // Create indexes for better performance
    $db->exec("CREATE INDEX idx_productos_vendedor ON productos(vendedor_id)");
    $db->exec("CREATE INDEX idx_productos_categoria ON productos(categoria_id)");
    $db->exec("CREATE INDEX idx_pedidos_comprador ON pedidos(comprador_id)");
    $db->exec("CREATE INDEX idx_pedidos_vendedor ON pedidos(vendedor_id)");
    $db->exec("CREATE INDEX idx_pedidos_estado ON pedidos(estado_orden)");
    $db->exec("CREATE INDEX idx_pedidos_pago_estado ON pedidos(estado_pago)");
    $db->exec("CREATE INDEX idx_pedidos_fecha ON pedidos(created_at)");
    $db->exec("CREATE INDEX idx_favoritos_usuario ON favoritos(usuario_id)");
    $db->exec("CREATE INDEX idx_carrito_usuario ON carrito(usuario_id)");

    // Re-enable foreign key constraints
    $db->exec("SET FOREIGN_KEY_CHECKS=1");

    echo "Migración completada con éxito.\n";

} catch (PDOException $e) {
    die("Error en la migración: " . $e->getMessage());
}

// Function to generate a random string for order numbers
function generarNumeroPedido($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return 'ORD-' . date('Ymd') . '-' . $randomString;
}
?>
