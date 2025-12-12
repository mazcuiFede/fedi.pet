<?php

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $celular = htmlspecialchars(trim($_POST['celular']));
    $direccion = htmlspecialchars(trim($_POST['direccion']));
    $comentarios = htmlspecialchars(trim($_POST['comentarios']));

    if (!empty($nombre) && !empty($celular) && !empty($direccion)) {

        try {
            $mail = new PHPMailer;
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com.ar'; // Servidor SMTP (puedes usar el de tu proveedor)
            $mail->SMTPAuth = true;
            $mail->Username = 'no-reply@clokteam.com'; // Tu correo
            $mail->Password = 'Testeo123.'; // Tu contraseña
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Cifrado
            $mail->Port = 587; // Puerto para TLS
            $mail->CharSet = 'UTF-8'; // Codificación UTF-8

            // Configuración del correo
            $mail->setFrom('no-reply@clokteam.com', 'FediPet Website'); // Correo y nombre del remitente
            $mail->addAddress('matias.azcui@gmail.com', 'FediPet'); // Correo y nombre del destinatario
            // $mail->addAddress('fedi.pet25@gmail.com', 'FediPet'); // Email adicional si lo necesitas

            // Contenido del mensaje
            $mail->isHTML(true); // Usar HTML
            $mail->Subject = 'Nuevo Pedido FediPet - Datos de Envío';

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
                    .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; border-radius: 5px; }
                    .field { margin-bottom: 15px; padding: 10px; background: white; border-left: 3px solid #4CAF50; }
                    .label { font-weight: bold; color: #555; }
                    .value { color: #333; margin-top: 5px; }
                    .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>🐾 Nuevo Pedido FediPet</h2>
                        <p>Datos de Envío Recibidos</p>
                    </div>
                    <div class="content">
                        <div class="field">
                            <div class="label">Nombre Completo:</div>
                            <div class="value">' . $nombre . '</div>
                        </div>
                        <div class="field">
                            <div class="label">Número de Celular:</div>
                            <div class="value">' . $celular . '</div>
                        </div>
                        <div class="field">
                            <div class="label">Dirección de Envío:</div>
                            <div class="value">' . nl2br($direccion) . '</div>
                        </div>';

            if (!empty($comentarios)) {
                $mail->Body .= '
                        <div class="field">
                            <div class="label">Comentarios Adicionales:</div>
                            <div class="value">' . nl2br($comentarios) . '</div>
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
            $mail->AltBody .= "\nFecha: " . date('d/m/Y H:i:s');

            // Enviar el correo
            $mail->send();

            // Retornar JSON para AJAX
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Email enviado correctamente']);
            exit();

        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje: ' . $mail->ErrorInfo]);
            exit();
        }
    } else {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Por favor, completa todos los campos requeridos.']);
        exit();
    }
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit();
}
?>