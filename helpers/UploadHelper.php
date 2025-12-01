<?php

class UploadHelper {
    private $config;
    private $allowedTypes;
    private $maxSize;
    private $uploadDir;
    private $errors = [];

    public function __construct($config = []) {
        $defaults = [
            'upload_dir' => __DIR__ . '/../uploads/',
            'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
            'max_size' => 5 * 1024 * 1024, // 5MB
            'encrypt_name' => true,
            'overwrite' => false,
            'create_dirs' => true
        ];

        $this->config = array_merge($defaults, $config);
        $this->allowedTypes = $this->config['allowed_types'];
        $this->maxSize = $this->config['max_size'];
        $this->uploadDir = rtrim($this->config['upload_dir'], '/') . '/';
        
        // Create upload directory if it doesn't exist
        if ($this->config['create_dirs'] && !is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload a single file
     */
    public function upload($fieldName) {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return [
                'success' => false,
                'error' => 'No file was uploaded.'
            ];
        }

        $file = $_FILES[$fieldName];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => $this->getUploadError($file['error'])
            ];
        }

        // Validate file type
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $this->allowedTypes)) {
            return [
                'success' => false,
                'error' => 'Tipo de archivo no permitido. Formatos aceptados: ' . 
                          implode(', ', $this->allowedTypes)
            ];
        }

        // Validate file size
        if ($file['size'] > $this->maxSize) {
            return [
                'success' => false,
                'error' => 'El archivo es demasiado grande. Tamaño máximo: ' . 
                          $this->formatBytes($this->maxSize)
            ];
        }

        // Generate unique filename
        $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $this->config['encrypt_name'] 
            ? md5(uniqid() . time()) . '.' . $fileExt
            : $this->sanitizeFileName($file['name']);

        $filePath = $this->uploadDir . $fileName;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Return relative path from web root
            $webPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filePath);
            $webPath = str_replace('\\', '/', $webPath); // Fix Windows paths
            
            return [
                'success' => true,
                'path' => $webPath,
                'name' => $fileName,
                'type' => $fileType,
                'size' => $file['size']
            ];
        }

        return [
            'success' => false,
            'error' => 'Error al subir el archivo.'
        ];
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple($fieldName) {
        if (!isset($_FILES[$fieldName]) || 
            !is_array($_FILES[$fieldName]['name']) || 
            empty($_FILES[$fieldName]['name'][0])) {
            return [
                'success' => false,
                'error' => 'No files were uploaded.'
            ];
        }

        $files = [];
        $uploaded = [];
        $errors = [];

        // Reorganize the $_FILES array
        foreach ($_FILES[$fieldName] as $key => $values) {
            foreach ($values as $index => $value) {
                $files[$index][$key] = $value;
            }
        }

        // Process each file
        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result = $this->uploadFile($file);
            if ($result['success']) {
                $uploaded[] = $result;
            } else {
                $errors[] = $file['name'] . ': ' . $result['error'];
            }
        }

        if (!empty($uploaded)) {
            return [
                'success' => true,
                'files' => $uploaded,
                'paths' => array_column($uploaded, 'path'),
                'errors' => $errors
            ];
        }

        return [
            'success' => false,
            'error' => implode(' ', $errors)
        ];
    }

    /**
     * Helper method to upload a single file from the multiple files array
     */
    private function uploadFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => $this->getUploadError($file['error'])
            ];
        }

        // Validate file type
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $this->allowedTypes)) {
            return [
                'success' => false,
                'error' => 'Tipo de archivo no permitido.'
            ];
        }

        // Validate file size
        if ($file['size'] > $this->maxSize) {
            return [
                'success' => false,
                'error' => 'Archivo demasiado grande.'
            ];
        }

        // Generate unique filename
        $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $this->config['encrypt_name'] 
            ? md5(uniqid() . time()) . '.' . $fileExt
            : $this->sanitizeFileName($file['name']);

        $filePath = $this->uploadDir . $fileName;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Return relative path from web root
            $webPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filePath);
            $webPath = str_replace('\\', '/', $webPath); // Fix Windows paths
            
            return [
                'success' => true,
                'path' => $webPath,
                'name' => $fileName,
                'type' => $fileType,
                'size' => $file['size']
            ];
        }

        return [
            'success' => false,
            'error' => 'Error al subir el archivo.'
        ];
    }

    /**
     * Delete a file
     */
    public function deleteFile($filePath) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . ltrim($filePath, '/');
        
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }

    /**
     * Get upload error message
     */
    private function getUploadError($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL => 'El archivo fue subido solo parcialmente.',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la carga del archivo.',
        ];

        return $errors[$errorCode] ?? 'Error desconocido al subir el archivo.';
    }

    /**
     * Sanitize filename
     */
    private function sanitizeFileName($filename) {
        // Remove any character that is not alphanumeric, dash, underscore, or dot
        $filename = preg_replace("/[^a-zA-Z0-9-_.]/", "-", $filename);
        // Remove multiple consecutive dashes
        $filename = preg_replace("/-+/", "-", $filename);
        // Remove leading/trailing dashes and dots
        $filename = trim($filename, "-. ");
        
        return $filename;
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get the last error message
     */
    public function getError() {
        return end($this->errors) ?: '';
    }

    /**
     * Get all error messages
     */
    public function getErrors() {
        return $this->errors;
    }
}
