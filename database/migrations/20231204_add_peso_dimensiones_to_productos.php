<?php

class AddPesoDimensionesToProductos {
    public function up($db) {
        try {
            // Agregar columna de peso (en gramos)
            $db->exec("ALTER TABLE productos ADD COLUMN peso_gramos INT NULL DEFAULT NULL AFTER stock");
            
            // Agregar columnas de dimensiones (en milímetros)
            $db->exec("ALTER TABLE productos ADD COLUMN largo_mm INT NULL DEFAULT NULL AFTER peso_gramos");
            $db->exec("ALTER TABLE productos ADD COLUMN ancho_mm INT NULL DEFAULT NULL AFTER largo_mm");
            $db->exec("ALTER TABLE productos ADD COLUMN alto_mm INT NULL DEFAULT NULL AFTER ancho_mm");
            
            return true;
        } catch (PDOException $e) {
            error_log("Error en migración AddPesoDimensionesToProductos: " . $e->getMessage());
            return false;
        }
    }
    
    public function down($db) {
        try {
            // Revertir los cambios si es necesario
            $db->exec("ALTER TABLE productos DROP COLUMN IF EXISTS peso_gramos");
            $db->exec("ALTER TABLE productos DROP COLUMN IF EXISTS largo_mm");
            $db->exec("ALTER TABLE productos DROP COLUMN IF EXISTS ancho_mm");
            $db->exec("ALTER TABLE productos DROP COLUMN IF EXISTS alto_mm");
            
            return true;
        } catch (PDOException $e) {
            error_log("Error al revertir migración AddPesoDimensionesToProductos: " . $e->getMessage());
            return false;
        }
    }
}
