-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 09-12-2025 a las 10:15:20
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `silco_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carrito`
--

INSERT INTO `carrito` (`id`, `usuario_id`, `producto_id`, `cantidad`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(35, 1, 12, 1, '2025-12-09 09:07:20', '2025-12-09 09:07:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carts`
--

CREATE TABLE `carts` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `carts`
--
DELIMITER $$
CREATE TRIGGER `update_carts_updated_at` BEFORE UPDATE ON `carts` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart_items`
--

CREATE TABLE `cart_items` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `cart_id` varchar(36) NOT NULL,
  `product_id` varchar(36) NOT NULL,
  `variant_id` varchar(36) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `cart_items`
--
DELIMITER $$
CREATE TRIGGER `update_cart_items_updated_at` BEFORE UPDATE ON `cart_items` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `imagen`, `fecha_creacion`) VALUES
(1, 'Alimentos', NULL, NULL, '2025-12-09 09:06:58'),
(2, 'Electrodomésticos', NULL, NULL, '2025-12-09 09:06:58'),
(3, 'Herramientas', NULL, NULL, '2025-12-09 09:06:58'),
(4, 'Moda', NULL, NULL, '2025-12-09 09:06:58'),
(5, 'Salud y Belleza', NULL, NULL, '2025-12-09 09:06:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_backup`
--

CREATE TABLE `categorias_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias_backup`
--

INSERT INTO `categorias_backup` (`id`, `nombre`, `descripcion`, `imagen`, `fecha_creacion`) VALUES
(1, 'Electrodomésticos', 'Los mejores electrodomésticos para tu hogar', NULL, '2025-11-25 22:09:03'),
(2, 'Moda', 'Ropa y accesorios de moda', NULL, '2025-11-25 22:09:03'),
(3, 'Alimentos', 'Productos alimenticios de calidad', NULL, '2025-11-25 22:09:03'),
(4, 'Salud y belleza', 'Cuidado personal y belleza', NULL, '2025-11-25 22:09:03'),
(5, 'Herramientas', 'Herramientas para todo tipo de trabajos', NULL, '2025-11-25 22:09:03'),
(11, 'Electrodomésticos', 'Los mejores electrodomésticos para tu hogar', NULL, '2025-12-08 17:30:52'),
(12, 'Moda', 'Ropa y accesorios de moda', NULL, '2025-12-08 17:30:52'),
(13, 'Alimentos', 'Productos alimenticios de calidad', NULL, '2025-12-08 17:30:52'),
(14, 'Salud y belleza', 'Cuidado personal y belleza', NULL, '2025-12-08 17:30:52'),
(15, 'Herramientas', 'Herramientas para todo tipo de trabajos', NULL, '2025-12-08 17:30:52'),
(16, 'Electrodomésticos', 'Los mejores electrodomésticos para tu hogar', NULL, '2025-12-08 17:30:52'),
(17, 'Moda', 'Ropa y accesorios de moda', NULL, '2025-12-08 17:30:52'),
(18, 'Alimentos', 'Productos alimenticios de calidad', NULL, '2025-12-08 17:30:52'),
(19, 'Salud y belleza', 'Cuidado personal y belleza', NULL, '2025-12-08 17:30:52'),
(20, 'Herramientas', 'Herramientas para todo tipo de trabajos', NULL, '2025-12-08 17:30:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` varchar(36) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `categories`
--
DELIMITER $$
CREATE TRIGGER `update_categories_updated_at` BEFORE UPDATE ON `categories` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios`
--

CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `comentario` text NOT NULL,
  `puntuacion` tinyint(1) NOT NULL CHECK (`puntuacion` between 1 and 5),
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupones`
--

CREATE TABLE `cupones` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_descuento` enum('porcentaje','monto_fijo') NOT NULL,
  `valor_descuento` decimal(10,2) NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `usos_maximos` int(11) DEFAULT NULL,
  `usos_actuales` int(11) DEFAULT 0,
  `minimo_compra` decimal(10,2) DEFAULT 0.00,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_default_billing` tinyint(1) DEFAULT 0,
  `is_default_shipping` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `customer_addresses`
--
DELIMITER $$
CREATE TRIGGER `update_customer_addresses_updated_at` BEFORE UPDATE ON `customer_addresses` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pedido`
--

CREATE TABLE `detalles_pedido` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `discounts`
--

CREATE TABLE `discounts` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` varchar(20) NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `discounts`
--
DELIMITER $$
CREATE TRIGGER `update_discounts_updated_at` BEFORE UPDATE ON `discounts` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_estados_pedido`
--

CREATE TABLE `historial_estados_pedido` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) NOT NULL,
  `comentario` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `imagenes_producto`
--

CREATE TABLE `imagenes_producto` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `imagen_url` varchar(255) NOT NULL,
  `orden` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `items_carrito`
--

CREATE TABLE `items_carrito` (
  `id` int(11) NOT NULL,
  `carrito_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `items_pedido`
--

CREATE TABLE `items_pedido` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `order_number` varchar(50) NOT NULL,
  `user_id` varchar(36) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `subtotal_amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `billing_address_id` varchar(36) DEFAULT NULL,
  `shipping_address_id` varchar(36) DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `orders`
--
DELIMITER $$
CREATE TRIGGER `set_order_number` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    DECLARE date_str VARCHAR(8);
    DECLARE id_str VARCHAR(8);
    
    -- Format date as YYYYMMDD
    SET date_str = DATE_FORMAT(NEW.created_at, '%Y%m%d');
    
    -- Format ID as 8-digit string with leading zeros
    SET id_str = LPAD(REPLACE(NEW.id, '-', ''), 8, '0');
    
    -- Set the order number
    SET NEW.order_number = CONCAT('ORD', date_str, '-', SUBSTRING(id_str, 1, 8));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_orders_updated_at` BEFORE UPDATE ON `orders` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `order_id` varchar(36) NOT NULL,
  `product_id` varchar(36) NOT NULL,
  `variant_id` varchar(36) DEFAULT NULL,
  `seller_id` varchar(36) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `order_items`
--
DELIMITER $$
CREATE TRIGGER `update_order_items_updated_at` BEFORE UPDATE ON `order_items` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_stock_after_order_item_delete` AFTER DELETE ON `order_items` FOR EACH ROW BEGIN
    IF OLD.variant_id IS NOT NULL THEN
        UPDATE product_variants 
        SET stock_quantity = stock_quantity + OLD.quantity,
            updated_at = NOW()
        WHERE id = OLD.variant_id;
    ELSE
        UPDATE products 
        SET stock_quantity = stock_quantity + OLD.quantity,
            updated_at = NOW()
        WHERE id = OLD.product_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_stock_after_order_item_insert` AFTER INSERT ON `order_items` FOR EACH ROW BEGIN
    IF NEW.variant_id IS NOT NULL THEN
        UPDATE product_variants 
        SET stock_quantity = stock_quantity - NEW.quantity,
            updated_at = NOW()
        WHERE id = NEW.variant_id;
    ELSE
        UPDATE products 
        SET stock_quantity = stock_quantity - NEW.quantity,
            updated_at = NOW()
        WHERE id = NEW.product_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_statuses`
--

CREATE TABLE `order_statuses` (
  `status_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `order_statuses`
--

INSERT INTO `order_statuses` (`status_name`) VALUES
('cancelled'),
('delivered'),
('pending'),
('processing'),
('shipped');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payments`
--

CREATE TABLE `payments` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `order_id` varchar(36) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `payments`
--
DELIMITER $$
CREATE TRIGGER `update_payments_updated_at` BEFORE UPDATE ON `payments` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_statuses`
--

CREATE TABLE `payment_statuses` (
  `status_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `payment_statuses`
--

INSERT INTO `payment_statuses` (`status_name`) VALUES
('completed'),
('failed'),
('pending'),
('refunded');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payout_items`
--

CREATE TABLE `payout_items` (
  `payout_id` varchar(36) NOT NULL,
  `order_item_id` varchar(36) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `direccion_envio` text NOT NULL,
  `ciudad_envio` varchar(100) NOT NULL,
  `codigo_postal_envio` varchar(20) NOT NULL,
  `pais_envio` varchar(100) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `envio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `impuestos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','procesando','enviado','entregado','cancelado') DEFAULT 'pendiente',
  `metodo_pago` varchar(50) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `vendedor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles_vendedor`
--

CREATE TABLE `perfiles_vendedor` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre_tienda` varchar(100) NOT NULL,
  `rut_documento` varchar(20) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `email_empresa` varchar(255) NOT NULL,
  `descripcion_tienda` text DEFAULT NULL,
  `telefono_contacto` varchar(20) DEFAULT NULL,
  `direccion_tienda` text DEFAULT NULL,
  `ciudad_tienda` varchar(100) DEFAULT NULL,
  `pais_tienda` varchar(100) DEFAULT NULL,
  `redes_sociales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`redes_sociales`)),
  `calificacion_promedio` decimal(3,2) DEFAULT 0.00,
  `total_ventas` int(11) DEFAULT 0,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `vendedor_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_descuento` decimal(10,2) DEFAULT NULL,
  `precio_oferta` decimal(10,2) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `peso_gramos` int(11) DEFAULT NULL,
  `largo_mm` int(11) DEFAULT NULL,
  `ancho_mm` int(11) DEFAULT NULL,
  `alto_mm` int(11) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `imagen_principal` varchar(255) DEFAULT NULL,
  `condicion` enum('nuevo','usado','reacondicionado') DEFAULT 'nuevo',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `umbral_stock` int(11) DEFAULT 5,
  `en_oferta` tinyint(1) DEFAULT 0,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `etiquetas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`etiquetas`)),
  `visitas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `vendedor_id`, `categoria_id`, `nombre`, `descripcion`, `precio`, `precio_descuento`, `precio_oferta`, `stock`, `peso_gramos`, `largo_mm`, `ancho_mm`, `alto_mm`, `marca`, `modelo`, `sku`, `imagen_principal`, `condicion`, `activo`, `fecha_creacion`, `fecha_actualizacion`, `umbral_stock`, `en_oferta`, `fecha_publicacion`, `etiquetas`, `visitas`) VALUES
(1, 4, 1, 'Arroz Blanco 1kg', 'Arroz blanco de grano largo, ideal para acompañar tus comidas', 25.99, NULL, 15.99, 50, 1000, 200, 100, 50, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(2, 4, 1, 'Frijoles Negros 500g', 'Frijoles negros de la mejor calidad, listos para cocinar', 18.50, NULL, 12.99, 75, 500, 150, 100, 30, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(3, 4, 1, 'Aceite de Oliva 500ml', 'Aceite de oliva extra virgen, primera prensada en frío', 89.90, NULL, 75.50, 30, 550, 100, 80, 200, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(4, 4, 1, 'Leche Entera 1L', 'Leche entera pasteurizada, rica en calcio', 25.50, NULL, 22.90, 100, 1030, 100, 70, 200, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(5, 4, 1, 'Huevo Blanco 30 piezas', 'Huevo blanco fresco de gallina feliz', 120.00, NULL, 99.90, 40, 1800, 300, 200, 150, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(6, 4, 1, 'Manzana Roja 1kg', 'Manzana roja delicia, dulce y crujiente', 45.00, NULL, 39.90, 80, 1000, 200, 150, 100, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(7, 4, 1, 'Pasta Spaghetti 500g', 'Pasta italiana de sémola de trigo duro', 28.50, NULL, 24.90, 60, 500, 250, 100, 30, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(8, 4, 1, 'Atún en Agua 140g', 'Lomitos de atún en agua, alto en proteínas', 22.50, NULL, 18.90, 120, 150, 80, 60, 30, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(9, 4, 1, 'Galletas de Avena 300g', 'Galletas de avena con trozos de chocolate', 45.90, NULL, 39.90, 90, 300, 200, 100, 50, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(10, 4, 1, 'Café Molido 250g', 'Café 100% arábica, tostado medio', 149.90, NULL, 129.90, 40, 250, 150, 100, 50, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(11, 4, 2, 'Licuadora Profesional', 'Licuadora de 1000W con 6 velocidades', 1999.00, NULL, 1599.00, 15, 3500, 200, 200, 400, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(12, 4, 2, 'Horno Tostador 20L', 'Horno tostador con temporizador y bandeja extraíble', 899.00, NULL, 749.00, 10, 2800, 350, 250, 200, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(13, 4, 2, 'Batidora de Mano', 'Batidora de mano con 5 velocidades', 599.00, NULL, 499.00, 20, 1200, 300, 100, 100, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(14, 4, 2, 'Cafetera Programable', 'Cafetera de 12 tazas con temporizador', 1299.00, NULL, 1099.00, 12, 2500, 250, 180, 300, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(15, 4, 2, 'Sandwichera', 'Sandwichera antiadherente con luz indicadora', 399.00, NULL, 329.00, 25, 1800, 300, 200, 100, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(16, 4, 2, 'Plancha de Vapor', 'Plancha de vapor con suela antiadherente', 599.00, NULL, 499.00, 18, 1500, 300, 150, 150, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(17, 4, 2, 'Ventilador de Torre', 'Ventilador de torre oscilante con control remoto', 1299.00, NULL, 1099.00, 8, 4500, 300, 300, 800, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(18, 4, 2, 'Aspiradora Sin Bolsa', 'Aspiradora potente con filtro HEPA', 1799.00, NULL, 1499.00, 6, 5200, 350, 300, 250, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(19, 4, 2, 'Exprimidor de Cítricos', 'Exprimidor eléctrico para naranjas y limones', 349.00, NULL, 299.00, 30, 2300, 200, 200, 300, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(20, 4, 2, 'Hervidor Eléctrico', 'Hervidor de agua con apagado automático', 429.00, NULL, 369.00, 22, 1200, 200, 150, 200, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(21, 4, 3, 'Juego de Destornilladores', 'Set de 6 destornilladores profesionales', 349.00, NULL, 299.00, 40, 800, 300, 200, 50, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(22, 4, 3, 'Taladro Inalámbrico', 'Taladro inalámbrico de 18V con 2 baterías', 1299.00, NULL, 1099.00, 12, 2800, 300, 250, 200, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(23, 4, 3, 'Juego de Llaves Mixtas', 'Juego de 10 llaves mixtas de acero cromo vanadio', 429.00, NULL, 369.00, 35, 600, 250, 150, 30, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(24, 4, 3, 'Martillo de Uña', 'Martillo de uña de 16oz con mango de fibra de vidrio', 199.00, NULL, 159.00, 50, 700, 350, 100, 50, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(25, 4, 3, 'Caja de Herramientas', 'Caja de herramientas de plástico resistente', 599.00, NULL, 499.00, 20, 3500, 500, 300, 200, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(26, 4, 3, 'Sierra Circular', 'Sierra circular inalámbrica con batería de litio', 1899.00, NULL, 1599.00, 8, 4200, 350, 250, 300, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(27, 4, 3, 'Juego de Pinzas', 'Set de 5 pinzas de precisión', 279.00, NULL, 229.00, 45, 400, 200, 100, 20, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(28, 4, 3, 'Nivel Láser', 'Nivel láser de línea cruzada con trípode', 899.00, NULL, 759.00, 15, 1500, 200, 150, 150, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(29, 4, 3, 'Pulidora Orbital', 'Pulidora orbital de 6\" con control de velocidad', 1299.00, NULL, 1099.00, 10, 3200, 250, 200, 200, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(30, 4, 3, 'Escuadra de Acero', 'Escuadra de acero inoxidable de 12 pulgadas', 159.00, NULL, 129.00, 60, 300, 300, 300, 10, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(31, 4, 4, 'Jeans Slim Fit', 'Jeans ajustados de mezclilla de alta calidad', 899.00, NULL, 749.00, 30, 500, 300, 200, 10, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(32, 4, 4, 'Camisa de Vestir', 'Camisa de vestir de algodón 100%', 599.00, NULL, 499.00, 45, 300, 400, 300, 20, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(33, 4, 4, 'Zapatos Casuales', 'Zapatos casuales de piel sintética', 799.00, NULL, 659.00, 25, 1200, 280, 100, 120, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(34, 4, 4, 'Chamarra Ligera', 'Chamara ligera resistente al agua', 699.00, NULL, 599.00, 20, 800, 400, 350, 50, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(35, 4, 4, 'Vestido Floral', 'Vestido floral de verano', 599.00, NULL, 499.00, 35, 400, 350, 300, 10, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(36, 4, 4, 'Sudadera con Capucha', 'Sudadera de algodón con capucha', 499.00, NULL, 429.00, 40, 700, 400, 350, 30, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(37, 4, 4, 'Zapatillas Deportivas', 'Zapatillas deportivas para correr', 1299.00, NULL, 1099.00, 15, 900, 280, 120, 100, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(38, 4, 4, 'Bolso de Mano', 'Bolso de mano de cuero sintético', 699.00, NULL, 599.00, 18, 600, 350, 150, 250, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(39, 4, 4, 'Gorra Ajustable', 'Gorra deportiva ajustable', 249.00, NULL, 199.00, 50, 200, 250, 200, 100, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(40, 4, 4, 'Cinturón de Piel', 'Cinturón de piel genuina', 349.00, NULL, 299.00, 30, 300, 1000, 50, 10, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(41, 4, 5, 'Crema Hidratante', 'Crema hidratante facial 50ml', 349.00, NULL, 299.00, 100, 100, 80, 60, 30, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(42, 4, 5, 'Shampoo Anticaída', 'Shampoo con biotina para caída del cabello', 249.00, NULL, 199.00, 80, 500, 200, 80, 80, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(43, 4, 5, 'Protector Solar FPS 50', 'Protector solar de amplio espectro', 279.00, NULL, 229.00, 65, 150, 100, 60, 30, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(44, 4, 5, 'Desodorante en Barra', 'Desodorante 48 horas de protección', 89.00, NULL, 69.00, 120, 100, 150, 50, 50, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(45, 4, 5, 'Cepillo Dental Eléctrico', 'Cepillo dental recargable con temporizador', 599.00, NULL, 499.00, 40, 300, 200, 50, 50, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(46, 4, 5, 'Kit de Maquillaje', 'Kit completo de maquillaje profesional', 1299.00, NULL, 1099.00, 15, 1200, 250, 200, 100, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(47, 4, 5, 'Perfume 100ml', 'Fragancia duradera de edición especial', 1299.00, NULL, 1099.00, 20, 200, 150, 50, 50, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(48, 4, 5, 'Mascarilla Facial', 'Mascarilla de arcilla purificante', 149.00, NULL, 129.00, 90, 100, 120, 80, 20, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(49, 4, 5, 'Cortauñas Profesional', 'Kit de cuidado de uñas profesional', 299.00, NULL, 249.00, 50, 150, 150, 80, 30, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0),
(50, 4, 5, 'Crema Antiarrugas', 'Crema antiarrugas con ácido hialurónico', 499.00, NULL, 429.00, 30, 120, 100, 60, 60, NULL, NULL, NULL, NULL, 'nuevo', 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', 5, 0, '2025-12-09 09:06:58', NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `seller_id` varchar(36) NOT NULL,
  `category_id` varchar(36) DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_at_price` decimal(10,2) DEFAULT NULL,
  `cost_per_item` decimal(10,2) DEFAULT NULL,
  `is_taxable` tinyint(1) DEFAULT 1,
  `barcode` varchar(100) DEFAULT NULL,
  `track_inventory` tinyint(1) DEFAULT 1,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT 5,
  `weight` decimal(10,2) DEFAULT NULL,
  `length` decimal(10,2) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `requires_shipping` tinyint(1) DEFAULT 1,
  `seo_title` varchar(100) DEFAULT NULL,
  `seo_description` varchar(255) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `products`
--
DELIMITER $$
CREATE TRIGGER `update_products_updated_at` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_attributes`
--

CREATE TABLE `product_attributes` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `product_attributes`
--
DELIMITER $$
CREATE TRIGGER `update_product_attributes_updated_at` BEFORE UPDATE ON `product_attributes` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_attribute_values`
--

CREATE TABLE `product_attribute_values` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `product_id` varchar(36) NOT NULL,
  `attribute_id` varchar(36) NOT NULL,
  `value` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `product_attribute_values`
--
DELIMITER $$
CREATE TRIGGER `update_product_attribute_values_updated_at` BEFORE UPDATE ON `product_attribute_values` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_images`
--

CREATE TABLE `product_images` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `product_id` varchar(36) NOT NULL,
  `variant_id` varchar(36) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `product_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(100) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `product_reviews`
--
DELIMITER $$
CREATE TRIGGER `update_product_reviews_updated_at` BEFORE UPDATE ON `product_reviews` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_variants`
--

CREATE TABLE `product_variants` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `product_id` varchar(36) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `option1` varchar(50) DEFAULT NULL,
  `option2` varchar(50) DEFAULT NULL,
  `option3` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_at_price` decimal(10,2) DEFAULT NULL,
  `cost_per_item` decimal(10,2) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `weight` decimal(10,2) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `product_variants`
--
DELIMITER $$
CREATE TRIGGER `update_product_variants_updated_at` BEFORE UPDATE ON `product_variants` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_productos`
--

CREATE TABLE `reportes_productos` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `motivo` enum('falso','descripcion_enganosa','imagen_inapropiada','copyright','otro') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('pendiente','en_revision','resuelto','desestimado') DEFAULT 'pendiente',
  `fecha_reporte` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reseñas`
--

CREATE TABLE `reseñas` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `calificacion` tinyint(4) NOT NULL CHECK (`calificacion` >= 1 and `calificacion` <= 5),
  `comentario` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sellers`
--

CREATE TABLE `sellers` (
  `user_id` varchar(36) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `business_description` text DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_ratings` int(11) DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `sellers`
--
DELIMITER $$
CREATE TRIGGER `update_sellers_updated_at` BEFORE UPDATE ON `sellers` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seller_bank_accounts`
--

CREATE TABLE `seller_bank_accounts` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `seller_id` varchar(36) NOT NULL,
  `account_holder_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `bank_code` varchar(50) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `seller_bank_accounts`
--
DELIMITER $$
CREATE TRIGGER `update_seller_bank_accounts_updated_at` BEFORE UPDATE ON `seller_bank_accounts` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seller_payouts`
--

CREATE TABLE `seller_payouts` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `seller_id` varchar(36) NOT NULL,
  `bank_account_id` varchar(36) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `seller_payouts`
--
DELIMITER $$
CREATE TRIGGER `update_seller_payouts_updated_at` BEFORE UPDATE ON `seller_payouts` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shipments`
--

CREATE TABLE `shipments` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `order_id` varchar(36) NOT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `carrier` varchar(50) DEFAULT NULL,
  `service` varchar(50) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `tracking_url` text DEFAULT NULL,
  `shipping_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`shipping_address`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `shipments`
--
DELIMITER $$
CREATE TRIGGER `update_shipments_updated_at` BEFORE UPDATE ON `shipments` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shipment_items`
--

CREATE TABLE `shipment_items` (
  `shipment_id` varchar(36) NOT NULL,
  `order_item_id` varchar(36) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'customer',
  `is_email_verified` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `users`
--
DELIMITER $$
CREATE TRIGGER `update_users_updated_at` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` varchar(36) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` char(1) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `user_profiles`
--
DELIMITER $$
CREATE TRIGGER `update_user_profiles_updated_at` BEFORE UPDATE ON `user_profiles` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_roles`
--

CREATE TABLE `user_roles` (
  `role_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user_roles`
--

INSERT INTO `user_roles` (`role_name`) VALUES
('admin'),
('customer'),
('seller');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usos_cupones`
--

CREATE TABLE `usos_cupones` (
  `id` int(11) NOT NULL,
  `cupon_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `fecha_uso` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `codigo_postal` varchar(20) DEFAULT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `es_vendedor` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `remember_token` varchar(100) DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password`, `telefono`, `direccion`, `ciudad`, `codigo_postal`, `pais`, `es_vendedor`, `fecha_creacion`, `fecha_actualizacion`, `remember_token`, `token_expires_at`) VALUES
(1, 'Valentin', 'Hernandez Bruccoleri', 'valentinhernandez.17.01.2007@gmail.com', '$2y$10$oBWcoogb.1GoWez685pnGuJirvMEsehFDl0AV3QzF1DOn/ljCEcbO', '092673601', 'Mantagune 721', 'San José de Mayo', '80000', 'Uruguay', 1, '2025-11-28 14:51:28', '2025-12-07 20:45:56', NULL, NULL),
(2, 'Mateo', 'Hernandez Bruccoleri', 'papotemaloterey098@gmail.com', '$2y$10$rmvOvKXjRirVywFgy7uLfe4zTanh0JwIZq7YqMKAhbRA/i/YPMVpu', '092673601', 'Mantagune 721', 'San José de Mayo', '80000', 'Uruguay', 0, '2025-12-07 20:55:40', '2025-12-07 20:55:40', NULL, NULL),
(3, 'Rossana', 'Hernandez', 'papotemaloterey095@gmail.com', '$2y$10$kC4k01FOQavT0mWU7jsyHuPd8QIuk1caxDNqHrRD9ZAmUu5lg8/XS', '092673607', 'Solis', 'San José de Mayo', '80000', 'Uruguay', 1, '2025-12-07 20:57:29', '2025-12-07 20:57:29', NULL, NULL),
(4, 'Vendedor', 'Demo', 'vendedor@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-09 09:00:13', '2025-12-09 09:00:13', NULL, NULL),
(9, 'Admin', 'Silco', 'admin@silco.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, 1, '2025-12-09 09:06:58', '2025-12-09 09:06:58', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wishlists`
--

CREATE TABLE `wishlists` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `wishlists`
--
DELIMITER $$
CREATE TRIGGER `update_wishlists_updated_at` BEFORE UPDATE ON `wishlists` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wishlist_items`
--

CREATE TABLE `wishlist_items` (
  `wishlist_id` varchar(36) NOT NULL,
  `product_id` varchar(36) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cart_id` (`cart_id`,`product_id`,`variant_id`),
  ADD KEY `idx_cart_items_cart_id` (`cart_id`),
  ADD KEY `idx_cart_items_product_id` (`product_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_producto_id` (`producto_id`),
  ADD KEY `idx_usuario_id` (`usuario_id`);

--
-- Indices de la tabla `cupones`
--
ALTER TABLE `cupones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`usuario_id`,`producto_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `historial_estados_pedido`
--
ALTER TABLE `historial_estados_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `imagenes_producto`
--
ALTER TABLE `imagenes_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `items_carrito`
--
ALTER TABLE `items_carrito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carrito_id` (`carrito_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `items_pedido`
--
ALTER TABLE `items_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_user_id` (`user_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_payment_status` (`payment_status`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_order_id` (`order_id`),
  ADD KEY `idx_order_items_seller_id` (`seller_id`),
  ADD KEY `idx_order_items_product_id` (`product_id`);

--
-- Indices de la tabla `order_statuses`
--
ALTER TABLE `order_statuses`
  ADD PRIMARY KEY (`status_name`);

--
-- Indices de la tabla `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_order_id` (`order_id`),
  ADD KEY `idx_payments_status` (`status`);

--
-- Indices de la tabla `payment_statuses`
--
ALTER TABLE `payment_statuses`
  ADD PRIMARY KEY (`status_name`);

--
-- Indices de la tabla `payout_items`
--
ALTER TABLE `payout_items`
  ADD PRIMARY KEY (`payout_id`,`order_item_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pedido_usuario` (`usuario_id`),
  ADD KEY `idx_pedido_estado` (`estado`),
  ADD KEY `vendedor_id` (`vendedor_id`);

--
-- Indices de la tabla `perfiles_vendedor`
--
ALTER TABLE `perfiles_vendedor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_producto_vendedor` (`vendedor_id`),
  ADD KEY `idx_producto_categoria` (`categoria_id`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_products_seller_id` (`seller_id`),
  ADD KEY `idx_products_category_id` (`category_id`),
  ADD KEY `idx_products_slug` (`slug`),
  ADD KEY `idx_products_is_published` (`is_published`);

--
-- Indices de la tabla `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_id` (`product_id`,`attribute_id`);

--
-- Indices de la tabla `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_reviews_product_id` (`product_id`),
  ADD KEY `idx_product_reviews_user_id` (`user_id`);

--
-- Indices de la tabla `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_product_variants_product_id` (`product_id`);

--
-- Indices de la tabla `reportes_productos`
--
ALTER TABLE `reportes_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `reseñas`
--
ALTER TABLE `reseñas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rating` (`producto_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`user_id`);

--
-- Indices de la tabla `seller_bank_accounts`
--
ALTER TABLE `seller_bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `seller_payouts`
--
ALTER TABLE `seller_payouts`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shipments_order_id` (`order_id`);

--
-- Indices de la tabla `shipment_items`
--
ALTER TABLE `shipment_items`
  ADD PRIMARY KEY (`shipment_id`,`order_item_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indices de la tabla `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`role_name`);

--
-- Indices de la tabla `usos_cupones`
--
ALTER TABLE `usos_cupones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cupon_id` (`cupon_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `pedido_id` (`pedido_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD PRIMARY KEY (`wishlist_id`,`product_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cupones`
--
ALTER TABLE `cupones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT de la tabla `historial_estados_pedido`
--
ALTER TABLE `historial_estados_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `imagenes_producto`
--
ALTER TABLE `imagenes_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `items_carrito`
--
ALTER TABLE `items_carrito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `items_pedido`
--
ALTER TABLE `items_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `perfiles_vendedor`
--
ALTER TABLE `perfiles_vendedor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `reportes_productos`
--
ALTER TABLE `reportes_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reseñas`
--
ALTER TABLE `reseñas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usos_cupones`
--
ALTER TABLE `usos_cupones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD CONSTRAINT `detalles_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalles_pedido_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_estados_pedido`
--
ALTER TABLE `historial_estados_pedido`
  ADD CONSTRAINT `historial_estados_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_estados_pedido_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `imagenes_producto`
--
ALTER TABLE `imagenes_producto`
  ADD CONSTRAINT `imagenes_producto_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `items_carrito`
--
ALTER TABLE `items_carrito`
  ADD CONSTRAINT `items_carrito_ibfk_1` FOREIGN KEY (`carrito_id`) REFERENCES `carrito` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `items_carrito_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `items_pedido`
--
ALTER TABLE `items_pedido`
  ADD CONSTRAINT `items_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `items_pedido_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `perfiles_vendedor`
--
ALTER TABLE `perfiles_vendedor`
  ADD CONSTRAINT `perfiles_vendedor_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

--
-- Filtros para la tabla `reportes_productos`
--
ALTER TABLE `reportes_productos`
  ADD CONSTRAINT `reportes_productos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reportes_productos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reseñas`
--
ALTER TABLE `reseñas`
  ADD CONSTRAINT `reseñas_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reseñas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usos_cupones`
--
ALTER TABLE `usos_cupones`
  ADD CONSTRAINT `usos_cupones_ibfk_1` FOREIGN KEY (`cupon_id`) REFERENCES `cupones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usos_cupones_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usos_cupones_ibfk_3` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
