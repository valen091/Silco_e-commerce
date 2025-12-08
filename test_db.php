<?php
// Iniciar sesión al principio del script
session_start([
    'use_strict_mode' => true,
    'use_cookies' => 1,
    'cookie_httponly' => 1,
    'cookie_samesite' => 'Lax'
]);

require_once 'includes/functions.php';

// Verificar si la tabla de usuarios existe
function verificarTablaUsuarios($conn) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'usuarios'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error al verificar tabla usuarios: " . $e->getMessage());
        return false;
    }
}

// Verificar la estructura de la tabla usuarios
function verificarEstructuraTabla($conn) {
    try {
        $stmt = $conn->query("DESCRIBE usuarios");
        $campos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return in_array('es_vendedor', $campos);
    } catch (PDOException $e) {
        error_log("Error al verificar estructura de la tabla: " . $e->getMessage());
        return false;
    }
}

// Verificar si hay usuarios en la base de datos
function verificarUsuarios($conn) {
    try {
        $stmt = $conn->query("SELECT id, email, es_vendedor FROM usuarios");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $usuarios;
    } catch (PDOException $e) {
        error_log("Error al obtener usuarios: " . $e->getMessage());
        return [];
    }
}

// Ejecutar pruebas
$db = new Database();
$conn = $db->connect();

if ($conn) {
    echo "<h2>Prueba de conexión a la base de datos</h2>";
    
    // Verificar tabla usuarios
    echo "<h3>1. Verificando tabla de usuarios...</h3>";
    if (verificarTablaUsuarios($conn)) {
        echo "✅ La tabla 'usuarios' existe.<br>";
        
        // Verificar estructura
        echo "<h3>2. Verificando estructura de la tabla...</h3>";
        if (verificarEstructuraTabla($conn)) {
            echo "✅ La columna 'es_vendedor' existe en la tabla 'usuarios'.<br>";
        } else {
            echo "❌ La columna 'es_vendedor' NO existe en la tabla 'usuarios'.<br>";
            echo "<p>Ejecuta el siguiente comando SQL para agregar la columna:</p>";
            echo "<pre>ALTER TABLE usuarios ADD COLUMN es_vendedor TINYINT(1) DEFAULT 0;</pre>";
        }
        
        // Verificar usuarios
        echo "<h3>3. Usuarios en la base de datos:</h3>";
        $usuarios = verificarUsuarios($conn);
        if (!empty($usuarios)) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Email</th><th>Es Vendedor</th></tr>";
            foreach ($usuarios as $usuario) {
                echo sprintf(
                    "<tr><td>%s</td><td>%s</td><td>%s</td></tr>",
                    htmlspecialchars($usuario['id']),
                    htmlspecialchars($usuario['email']),
                    $usuario['es_vendedor'] ? 'Sí' : 'No'
                );
            }
            echo "</table>";
        } else {
            echo "No se encontraron usuarios en la base de datos.";
        }
    } else {
        echo "❌ La tabla 'usuarios' NO existe en la base de datos.<br>";
        echo "<p>Por favor, importa el archivo schema.sql para crear la estructura de la base de datos.</p>";
    }
} else {
    echo "<h2>❌ Error de conexión a la base de datos</h2>";
    echo "<p>No se pudo conectar a la base de datos. Verifica la configuración en config/database.php</p>";
}

// Mostrar información de la sesión actual
echo "<h3>4. Información de la sesión actual:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>5. Información del servidor:</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "</pre>";
?>

<h3>6. Solución de problemas:</h3>
<ol>
    <li>Si la tabla de usuarios no existe, importa el archivo schema.sql en tu base de datos.</li>
    <li>Si falta la columna 'es_vendedor', ejecuta: <pre>ALTER TABLE usuarios ADD COLUMN es_vendedor TINYINT(1) DEFAULT 0;</pre></li>
    <li>Para hacer que un usuario sea vendedor, ejecuta: <pre>UPDATE usuarios SET es_vendedor = 1 WHERE id = [ID_DEL_USUARIO];</pre></li>
    <li>Si hay problemas de sesión, intenta borrar las cookies del navegador o usar una ventana de incógnito.</li>
</ol>
