<?php
// Configuración de CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Si es una petición OPTIONS, terminar aquí
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Recibir datos del formulario
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$celular = isset($_POST['celular']) ? trim($_POST['celular']) : '';
$direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
$comentarios = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : '';

// Validar campos requeridos
if (empty($nombre) || empty($celular) || empty($direccion)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit();
}

// ============================================
// CONFIGURACIÓN SMTP - MODIFICAR ESTOS VALORES
// ============================================
$smtp_host = 'smtp.hostinger.com.ar';           // Servidor SMTP (ejemplo: smtp.gmail.com, smtp.office365.com)
$smtp_port = 587;                        // Puerto (587 para TLS, 465 para SSL)
$smtp_username = 'no-reply@clokteam.com';   // Tu email SMTP
$smtp_password = 'Testeo123.';   // Tu contraseña o App Password
$smtp_encryption = 'tls';                // 'tls' o 'ssl'

$email_from = 'no-reply@clokteam.com';      // Email remitente
$email_from_name = 'FediPet - Sitio Web'; // Nombre del remitente
$email_to = 'matias.azcui@gmail.com';    // Email destinatario
$email_subject = 'Nuevo Pedido FediPet - Datos de Envío';

// ============================================
// Usar PHPMailer si está disponible, sino usar mail()
// ============================================

// Intentar usar PHPMailer si existe
if (file_exists('PHPMailer/PHPMailer.php')) {
    require 'PHPMailer/PHPMailer.php';
    require 'PHPMailer/SMTP.php';
    require 'PHPMailer/Exception.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    try {
        $mail = new PHPMailer(true);

        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $smtp_encryption;
        $mail->Port = $smtp_port;
        $mail->CharSet = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom($email_from, $email_from_name);
        $mail->addAddress($email_to);
        $mail->addReplyTo($email_from, $email_from_name);

        // Contenido del email
        $mail->isHTML(true);
        $mail->Subject = $email_subject;

        // Cuerpo del email en HTML
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #555; }
                .value { color: #333; margin-top: 5px; }
                .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Nuevo Pedido FediPet</h2>
                    <p>Datos de Envío</p>
                </div>
                <div class="content">
                    <div class="field">
                        <div class="label">Nombre Completo:</div>
                        <div class="value">' . htmlspecialchars($nombre) . '</div>
                    </div>
                    <div class="field">
                        <div class="label">Número de Celular:</div>
                        <div class="value">' . htmlspecialchars($celular) . '</div>
                    </div>
                    <div class="field">
                        <div class="label">Dirección de Envío:</div>
                        <div class="value">' . nl2br(htmlspecialchars($direccion)) . '</div>
                    </div>';

        if (!empty($comentarios)) {
            $mail->Body .= '
                    <div class="field">
                        <div class="label">Comentarios Adicionales:</div>
                        <div class="value">' . nl2br(htmlspecialchars($comentarios)) . '</div>
                    </div>';
        }

        $mail->Body .= '
                </div>
                <div class="footer">
                    <p>Este email fue enviado desde el formulario de fedi.pet</p>
                    <p>' . date('d/m/Y H:i:s') . '</p>
                </div>
            </div>
        </body>
        </html>';

        // Versión texto plano
        $mail->AltBody = "NUEVO PEDIDO FEDIPET - DATOS DE ENVÍO\n\n";
        $mail->AltBody .= "Nombre: $nombre\n";
        $mail->AltBody .= "Celular: $celular\n";
        $mail->AltBody .= "Dirección: $direccion\n";
        if (!empty($comentarios)) {
            $mail->AltBody .= "Comentarios: $comentarios\n";
        }

        // Enviar email
        $mail->send();

        echo json_encode(['success' => true, 'message' => 'Email enviado correctamente']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al enviar el email: ' . $mail->ErrorInfo]);
    }

} else {
    // Usar la función mail() de PHP si PHPMailer no está disponible
    // NOTA: mail() requiere configuración del servidor

    $headers = "From: $email_from_name <$email_from>\r\n";
    $headers .= "Reply-To: $email_from\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $message = "<h2>NUEVO PEDIDO FEDIPET - DATOS DE ENVÍO</h2>";
    $message .= "<hr>";
    $message .= "<p><strong>Nombre:</strong> " . htmlspecialchars($nombre) . "</p>";
    $message .= "<p><strong>Celular:</strong> " . htmlspecialchars($celular) . "</p>";
    $message .= "<p><strong>Dirección:</strong><br>" . nl2br(htmlspecialchars($direccion)) . "</p>";

    if (!empty($comentarios)) {
        $message .= "<p><strong>Comentarios:</strong><br>" . nl2br(htmlspecialchars($comentarios)) . "</p>";
    }

    $message .= "<hr>";
    $message .= "<p><em>Enviado desde fedi.pet - " . date('d/m/Y H:i:s') . "</em></p>";

    if (mail($email_to, $email_subject, $message, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Email enviado correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al enviar el email']);
    }
}
?>
