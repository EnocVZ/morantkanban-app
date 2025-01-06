<?php

namespace App\Http\Controllers;

use App\Models\Assignee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

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
            $htmlTemplate = File::get(public_path('html/email_templates/assign_to_a_task.html'));
            $variables = [
                '{title}' => 'Nueva Tarea Asignada',
                '{workspacename}' => $task->project->workspace->name,
                '{proyect}' => $task->project->title,
                '{task}' => $task->title,
            ];
            
            // Reemplazar las variables en el template
            $html = str_replace(array_keys($variables), array_values($variables), $htmlTemplate);
           
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
