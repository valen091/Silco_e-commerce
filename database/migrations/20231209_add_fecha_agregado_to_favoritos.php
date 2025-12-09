<?php

class AddFechaAgregadoToFavoritos {
    public function up($conn) {
        // Check if the column already exists
        $checkColumn = $conn->query("SHOW COLUMNS FROM favoritos LIKE 'fecha_agregado'");
        
        if ($checkColumn->rowCount() === 0) {
            // Add the column if it doesn't exist
            $sql = "ALTER TABLE favoritos ADD COLUMN fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER producto_id";
            $conn->exec($sql);
            
            // Update existing records with current timestamp
            $conn->exec("UPDATE favoritos SET fecha_agregado = NOW() WHERE fecha_agregado IS NULL");
            
            error_log("Added fecha_agregado column to favoritos table");
        }
    }
    
    public function down($conn) {
        // This will remove the column if needed
        $conn->exec("ALTER TABLE favoritos DROP COLUMN IF EXISTS fecha_agregado");
    }
}
