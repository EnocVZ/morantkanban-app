<?php

namespace App\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailerHelper
{
    public static function sendMail($destinatarios, $asunto, $cuerpoHTML, $cuerpoTextoPlano="") {
        $mail = new PHPMailer(true);
        $remitente = "contacto@morant.com.mx";
        $nombreRemitente="Notificaciones Kanban Morant";
        $response = ["succces" => false, 'message' => ''];
        try {
            // Configuración del servidor SMTP de Gmail
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            $mail->SMTPSecure = env('MAIL_ENCRYPTION');
            $mail->Port = env('MAIL_PORT');
            $mail->CharSet = env('MAIL_CHARSET');

            // Configuración del remitente
            $mail->setFrom($remitente, $nombreRemitente);
    
       
            // Agregar destinatarios (puede ser un array con múltiples destinatarios)
            foreach ($destinatarios as $correo) {
                $mail->addAddress($correo);
            }
           
    
            // Contenido del correo
            $mail->isHTML(true);                                      // Indicar que el mensaje tiene formato HTML
            $mail->Subject = $asunto;                                 // Asunto del correo
            $mail->Body    = $cuerpoHTML;                             // Cuerpo del correo en formato HTML
            $mail->AltBody = $cuerpoTextoPlano;                       // Cuerpo del correo en texto plano (alternativo)
    
            // Enviar el correo
            $mail->send();
            $response = ["succces" => true, 'message' => 'Correo enviado exitosamente.'];
            return $response;
        } catch (Exception $e) {
            return $response = ["succces" => false, 'message' => $mail->ErrorInfo];
        }
    }
}