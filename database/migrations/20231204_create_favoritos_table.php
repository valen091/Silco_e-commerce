<?php

class CreateFavoritosTable {
    public function up($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS favoritos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            producto_id INT NOT NULL,
            fecha_agregado DATETIME NOT NULL,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_favorite (usuario_id, producto_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $conn->exec($sql);
    }
    
    public function down($conn) {
        $sql = "DROP TABLE IF EXISTS favoritos";
        $conn->exec($sql);
    }
}
