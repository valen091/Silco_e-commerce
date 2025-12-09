<?php

class CreateComentariosTable {
    public function up($db) {
        $query = "CREATE TABLE IF NOT EXISTS comentarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            producto_id INT NOT NULL,
            usuario_id INT NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            comentario TEXT NOT NULL,
            puntuacion TINYINT NOT NULL CHECK (puntuacion BETWEEN 1 AND 5),
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_producto_id (producto_id),
            INDEX idx_usuario_id (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($query);
    }

    public function down($db) {
        $db->exec("DROP TABLE IF EXISTS comentarios");
    }
}

// Check if this file is being executed directly (for testing)
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance()->getConnection();
    $migration = new self();
    $migration->up($db);
}
