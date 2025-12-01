<?php
class ContactoController {
    public function index() {
        $pageTitle = "Contacto - " . APP_NAME;
        require_once 'views/contacto/index.php';
    }

    public function enviarMensaje() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar y procesar el formulario de contacto
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING) ?? '';
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $asunto = filter_input(INPUT_POST, 'asunto', FILTER_SANITIZE_STRING) ?? '';
            $mensaje = filter_input(INPUT_POST, 'mensaje', FILTER_SANITIZE_STRING) ?? '';
            
            // Validar campos
            $errores = [];
            if (empty($nombre)) $errores[] = "El nombre es obligatorio";
            if (!$email) $errores[] = "El correo electrónico no es válido";
            if (empty($asunto)) $errores[] = "El asunto es obligatorio";
            if (empty($mensaje)) $errores[] = "El mensaje es obligatorio";

            if (empty($errores)) {
                // Aquí iría la lógica para enviar el correo
                // Por ahora, simulamos un envío exitoso
                $_SESSION['mensaje_exito'] = "Tu mensaje ha sido enviado. Nos pondremos en contacto contigo pronto.";
                unset($_SESSION['errores_contacto']);
                unset($_SESSION['datos_contacto']);
            } else {
                $_SESSION['errores_contacto'] = $errores;
                $_SESSION['datos_contacto'] = [
                    'nombre' => $nombre,
                    'email' => $_POST['email'] ?? '',
                    'asunto' => $asunto,
                    'mensaje' => $mensaje
                ];
            }
            
            header('Location: ' . BASE_URL . '/contacto');
            exit;
        }
    }
}
