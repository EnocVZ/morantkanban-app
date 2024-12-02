<?php

namespace App\Http\Controllers;

use App\Models\TaskNotification;
use Carbon\Traits\Creator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use App\Models\User;
use App\Models\Task;

class TaskNotificationController extends Controller
{
    //

    public function saveNew(Request $request){
        $requests = $request->all();
        $users = $request['users'];
        unset($request['users']);
        foreach ($users as $key => $value) {
            $payload = [
                'fromUser' => $request['fromUser'],
                'task' => $request['task'],
                'title' => $request['title'],
                'toUser' => $value,
                'wasRead' => false
            ];
           $notification = TaskNotification::create($payload);
           $notification->save();
        }
        $task = Task::where('id', $request['task'])
        ->with('project')
        ->with('project.workspace')
        ->first();
        $usuarios = $this->getUserMail($users);
        $html = "<b>Espacio de trabajo</b>: {$task->project->workspace->name} <b>Proyecto</b>: {$task->project->title} <b>Tarea</b>: {$task->title} <br/> <span>Comentario:</span><br>{$request['title']}";
        $this->enviarCorreo($usuarios, "Te mencionaron en un comentario", $html);
       $response = ["succces" => true];
        return response()->json($response);
    }

    public function wasReadNotification($id){
        $response = ["succces" => false, "data" => []];
        $notification = TaskNotification::where('idtask_notification', $id)->update(['wasRead' => true]);//->first();
        if($notification){
           $response = ["succces" => true,"data" => $notification];
        }
        return response()->json($response);
    }

    public function getUserMail($idList){
        $emails = User::whereIn('id', $idList)->pluck('email')->toArray();
        
        return $emails;
    }

    public function getNotificationByUser($auth_id){
        
        $notification = TaskNotification::where('toUser', $auth_id)
        ->with('task')
        ->with('task.project')
        ->with('task.project.workspace')
        ->orderBy('idtask_notification', 'desc')
        ->get()
        ->toArray();
        
        return response()->json($notification);
    }

    public function deleteNotification($id){
        $notification = TaskNotification::where('idtask_notification', $id);
        $result = null;
        if($notification){
            $result = $notification->delete();
        }
        return response()->json($result);
    }

    
function enviarCorreo($destinatarios, $asunto, $cuerpoHTML, $cuerpoTextoPlano="") {
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
        echo 'Correo enviado exitosamente.';
    } catch (Exception $e) {
        echo "El correo no pudo ser enviado. Error: {$mail->ErrorInfo}";
        
    }
    }
}