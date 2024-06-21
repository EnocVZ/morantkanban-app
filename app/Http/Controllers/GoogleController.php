<?php

namespace App\Http\Controllers;

use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Task;
use DateTime;
use DateTimeZone;
use App\Models\Assignee;
use App\Models\User;

class GoogleController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setAuthConfig(storage_path('app/google/credentials.json'));
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->addScope(Drive::DRIVE_FILE);
        $this->client->setRedirectUri(route('google.callback'));
    }

    public function redirectToGoogle()
    {
        $authUrl = $this->client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        $this->client->authenticate($request->code);
        $request->session()->put('google_token', $this->client->getAccessToken());

        return redirect()->route('home');
    }

    
    public function addEventToCalendar($taskId, Request $request)
    {
        $response = ['error'=> true,'message'=>""];
        $now = new DateTime();
        try {
            $task = Task::whereId($taskId)->first();
            // Crear un objeto DateTime a partir de la fecha original
            $endDate = new DateTime($task->due_date);

            $assigneeMail = Assignee::join('users', 'assignees.user_id', '=', 'users.id')
            ->where('assignees.task_id', $taskId)
            ->select('users.email')
            ->get();
            // Convertir al formato ISO 8601 con la zona horaria
            $endDate = $endDate->format('Y-m-d\TH:i:s');
            $endDateTime = new \DateTime($task->due_date);
            
            $token = session('google_token');
            if ($token) {
                $this->client->setAccessToken($token);
    
                $calendarService = new Calendar($this->client);
                
                $event = new Calendar\Event([
                    'summary' => $task->title,
                    'start' => [
                        'dateTime' => $endDate,
                        'timeZone' => 'America/Mexico_City', // Zona horaria de México
                    ],
                    'end' => [
                        'dateTime' => $endDate,
                        'timeZone' => 'America/Mexico_City', // Zona horaria de México
                    ]
                ]);
                
                
                $event->setAttendees($assigneeMail);
               
                
    
                $calendarService->events->insert('primary', $event, ['sendUpdates' => 'all']);
                $response['error'] = false;
                $response['message'] = 'Evento creado';
                $response['data'] = $assigneeMail;
                
            }else{

                $response['message'] = "Se necesita session de google";
            }
            //code...
        } catch (\Throwable $th) {
            $response['message'] = $th->getMessage();
        }
        return response()->json($response);
        //return redirect()->route('google.redirect');
    }



    public function uploadFileToDrive()
    {
        $token = session('google_token');
        if ($token) {
            $this->client->setAccessToken($token);

            $driveService = new Drive($this->client);

            $folderName = "Task";
            // Define la carpeta a crear
            $folderMetadata = new Drive\DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);

            // Crea la carpeta
            $folder = $driveService->files->create($folderMetadata, [
                'fields' => 'id'
            ]);
            $folderId = $folder->id;
            

            $file = new Drive\DriveFile();
            $file->setName('664cc7875a805-john-lennon.jpg');
            $file->setMimeType('application/octet-stream');
            // ID de la carpeta de destino en Google Drive
            
            $file->setParents([$folderId]);

            $content = file_get_contents(public_path('files/tasks/664cc7875a805-john-lennon.jpg'));
            $uploadedFile = $driveService->files->create($file, [
                'data' => $content,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            // Obtener el ID del archivo subido
        $fileId = $uploadedFile->id;

        
        // O bien, obtener el enlace directamente usando la API (si es necesario)
        $fileMetadata = $driveService->files->get($fileId, ['fields' => 'webViewLink']);
        $fileUrl = $fileMetadata->webViewLink;
        return $fileUrl;
        }

        return redirect()->route('google.redirect');
    }

    public function addEventToCalendaraaaa()
    {
        $token = session('google_token');
        if ($token) {
            $this->client->setAccessToken($token);

            $calendarService = new Calendar($this->client);

            $event = new Calendar\Event([
                'summary' => 'Test tareas prueba, invite',
                'start' => [
                    'dateTime' => '2024-06-14T10:00:00-07:00',
                    'timeZone' => 'America/Los_Angeles',
                ],
                'end' => [
                    'dateTime' => '2024-06-15T12:00:00-07:00',
                    'timeZone' => 'America/Los_Angeles',
                ],
            ]);
            // Añadir invitados al evento (en este caso, solo uno)
            $event->setAttendees([
                ['email' => "d47117263@gmail.com"],
            ]);

            $calendarService->events->insert('primary', $event);
            return 'Event created';
        }

        return redirect()->route('google.redirect');
    }
}
