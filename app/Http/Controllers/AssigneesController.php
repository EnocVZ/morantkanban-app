<?php

namespace App\Http\Controllers;

use App\Models\Assignee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use App\Models\User;
use App\Models\Task;
use App\Models\TaskNotification;



class AssigneesController extends Controller
{
    public function assignUserToTask(Request $request){
        $requestData = $request->all();
        $user_id = Auth()->id();
        $assignee = Assignee::where($requestData)->first();
        if(!empty($assignee)){
            $assignee->delete();
            $assignee = ['success' => true ];
        }else{
            $task = Task::where('id', $requestData['task_id'])
            ->with('project')
            ->with('project.workspace')
            ->first();
            $user = User::where('id', $requestData['user_id'])
            ->first();
            $assignee = Assignee::create($requestData);
            $payload = [
                'fromUser' => $user_id,
                'task' => $requestData['task_id'],
                'title' => '<p><strong>Nueva tarea asignada</p></strong>',
                'toUser' => $requestData['user_id'],
                'wasRead' => false,
                'notification_type'=>2
            ];
           $notification = TaskNotification::create($payload);
           $notification->save();
            $assignee->load('user');
            $html = "
                <!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                            padding: 0;
                            background-color: #f4f4f4;
                            color: #333333;
                        }

                        .email-container {
                            max-width: 600px;
                            margin: 20px auto;
                            background-color: #ffffff;
                            border-radius: 10px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                            overflow: hidden;
                        }

                        .header {
                            background-color: #007bff;
                            color: white;
                            padding: 15px;
                            text-align: center;
                        }

                        .header h1 {
                            margin: 0;
                            font-size: 24px;
                        }

                        .content {
                            padding: 20px;
                        }

                        .content h2 {
                            font-size: 20px;
                            margin-bottom: 10px;
                            color: #007bff;
                        }

                        .content p {
                            margin: 5px 0;
                            font-size: 16px;
                            line-height: 1.5;
                        }

                        .footer {
                            background-color: #f9f9f9;
                            text-align: center;
                            padding: 10px;
                            font-size: 14px;
                            color: #777777;
                        }

                        .footer a {
                            color: #007bff;
                            text-decoration: none;
                        }

                        .footer a:hover {
                            text-decoration: underline;
                        }
                    </style>
                </head>
                <body>
                    <div class='email-container'>
                        <!-- Encabezado -->
                        <div class='header'>
                            <h1>Nueva Tarea Asignada</h1>
                        </div>

                        <!-- Contenido principal -->
                        <div class='content'>
                            <h2>Detalles de la Tarea</h2>
                            <p><strong>Espacio de trabajo:</strong> {$task->project->workspace->name}</p>
                            <p><strong>Proyecto:</strong> {$task->project->title}</p>
                            <p><strong>Tarea:</strong> {$task->title}</p>
                            <p style='margin-top: 20px;'>Revisa tu espacio de trabajo para más detalles.</p>
                        </div>

                        <!-- Pie de página -->
                        <div class='footer'>
                            <p>Este correo es generado automáticamente. No respondas a este mensaje.</p>
                            <p>¿Tienes preguntas? <a href='mailto:soporte@tudominio.com'>Contáctanos</a></p>
                        </div>
                    </div>
                </body>
                </html>
                ";
        $this->enviarCorreo($user->email, "Te asignaron una nueva tarea", $html);
        }
        return response()->json($assignee);
    }

    function enviarCorreo($destinatario, $asunto, $cuerpoHTML, $cuerpoTextoPlano="") {
        $mail = new PHPMailer(true);
        $remitente = "contacto@morant.com.mx";
        $nombreRemitente="Notificaciones Kanban Morant";
        try {
            // Configuración del servidor SMTP de Gmail
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';          // Servidor SMTP de Gmail
            $mail->SMTPAuth = true;                  // Habilitar autenticación SMTP
            $mail->Username = $remitente; // Tu dirección de correo de Gmail
            $mail->Password = 'yutk emvm rmbw uypy';       // Tu contraseña o contraseña de aplicación (si tienes 2FA activado)
            $mail->SMTPSecure = 'tls';               // Habilitar encriptación TLS
            $mail->Port = 587;                       // Puerto SMTP para TLS (465 para SSL)
            $mail->CharSet = 'UTF-8'; 

            // Configuración del remitente
            $mail->setFrom($remitente, $nombreRemitente);
    
            // Agregar destinatarios (puede ser un array con múltiples destinatarios)
            $mail->addAddress($destinatario);
           
    
            // Contenido del correo
            $mail->isHTML(true);                                      // Indicar que el mensaje tiene formato HTML
            $mail->Subject = $asunto;                                 // Asunto del correo
            $mail->Body    = $cuerpoHTML;                             // Cuerpo del correo en formato HTML
            $mail->AltBody = $cuerpoTextoPlano;                       // Cuerpo del correo en texto plano (alternativo)
    
            // Enviar el correo
            $mail->send();
            echo 'Correo enviado exitosamente.';
        } catch (Exception $e) {
            echo "El correo no pudo ser enviado. Error: {$mail->ErrorInfo}";
            
        }
    }
}
