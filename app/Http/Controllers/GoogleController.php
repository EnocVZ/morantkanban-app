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
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class GoogleController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setAuthConfig(storage_path('app/google/credentials.json'));
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->addScope(Drive::DRIVE_FILE);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
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
        $token = $this->client->getAccessToken();

        if (isset($token['refresh_token'])) {
            file_put_contents(storage_path('app/google/google-refresh-token.json'), json_encode(['refresh_token' => $token['refresh_token'], 'google_token' => $token]));
        }

        $request->session()->put('google_token', $token);

        return redirect()->route('home');
    }

    public function addEventToCalendar($taskId, Request $request)
    {
        $response = ['error' => true, 'message' => ""];
        try {
            $task = Task::find($taskId);
            $endDate = new DateTime($task->due_date);

            $assignees = Assignee::join('users', 'assignees.user_id', '=', 'users.id')
                ->where('assignees.task_id', $taskId)
                ->select('users.email')
                ->get();

            $attendees = [];
            foreach ($assignees as $assignee) {
                $attendees[] = ['email' => $assignee->email];
            }

            $endDateFormatted = $endDate->format('Y-m-d\TH:i:s');

            $tokenData = json_decode(file_get_contents(storage_path('app/google/google-refresh-token.json')), true);
            $refreshToken = $tokenData['refresh_token'];

            $token = $tokenData['google_token'];
            if ($token) {
                $this->client->setAccessToken($token);

                
                if ($this->client->isAccessTokenExpired()) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newToken = $this->client->getAccessToken();

                    if (isset($newToken['refresh_token'])) {
                        file_put_contents(storage_path('app/google/google-refresh-token.json'), json_encode(['refresh_token' => $newToken['refresh_token'], 'google_token' => $newToken]));
                    }
                }

                $calendarService = new Google_Service_Calendar($this->client);

                $event = new Google_Service_Calendar_Event([
                    'summary' => $task->title,
                    'start' => [
                        'dateTime' => $endDateFormatted,
                        'timeZone' => 'America/Mexico_City',
                    ],
                    'end' => [
                        'dateTime' => $endDateFormatted,
                        'timeZone' => 'America/Mexico_City',
                    ],
                    'attendees' => $attendees,
                ]);

                $calendarService->events->insert('primary', $event, ['sendUpdates' => 'all']);
                $response['error'] = false;
                $response['message'] = 'Evento creado y notificaciones enviadas';
                $response['data'] = $attendees;
            } else {
                $response['message'] = "Se necesita sesión de Google";
            }
        } catch (\Throwable $th) {
            $response['message'] = $th->getMessage();
        }

        return response()->json($response);
    }

    public function uploadFileToDrive()
    {
        $tokenData = json_decode(file_get_contents(storage_path('app/google/google-refresh-token.json')), true);
        $refreshToken = $tokenData['refresh_token'];
        $token = $tokenData['google_token'];

        if ($token) {
            $this->client->setAccessToken($token);

            // Verificar si el token de acceso ha expirado
            if ($this->client->isAccessTokenExpired()) {
                $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                $newToken = $this->client->getAccessToken();

                // Actualizar el token en el archivo si ha cambiado
                if (isset($newToken['refresh_token'])) {
                    file_put_contents(storage_path('app/google/google-refresh-token.json'), json_encode(['refresh_token' => $newToken['refresh_token'], 'google_token' => $newToken]));
                }
            }

            $driveService = new Drive($this->client);

            $folderName = "Task";

            // Buscar si la carpeta ya existe
            $query = "mimeType='application/vnd.google-apps.folder' and name='$folderName' and trashed=false";
            $response = $driveService->files->listFiles([
                'q' => $query,
                'spaces' => 'drive',
                'fields' => 'files(id, name)'
            ]);

            $files = $response->files;

            if (count($files) > 0) {
                // Si la carpeta existe, usar su ID
                $folderId = $files[0]->id;
            } else {
                // Si la carpeta no existe, crearla
                $folderMetadata = new Drive\DriveFile([
                    'name' => $folderName,
                    'mimeType' => 'application/vnd.google-apps.folder'
                ]);

                $folder = $driveService->files->create($folderMetadata, [
                    'fields' => 'id'
                ]);
                $folderId = $folder->id;
            }

            // Subir el archivo a la carpeta
            $file = new Drive\DriveFile();
            $file->setName('664cc7875a805-john-lennon.jpg');
            $file->setMimeType('application/octet-stream');
            $file->setParents([$folderId]);

            $content = file_get_contents(public_path('files/tasks/664cc7875a805-john-lennon.jpg'));
            $uploadedFile = $driveService->files->create($file, [
                'data' => $content,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            $fileId = $uploadedFile->id;

            // Cambiar permisos para hacerlo público
            $permission = new Drive\Permission();
            $permission->setType('anyone');
            $permission->setRole('reader');
            $driveService->permissions->create($fileId, $permission);

            // Obtener el enlace público
            $fileMetadata = $driveService->files->get($fileId, ['fields' => 'webViewLink, webContentLink']);
            $fileUrl = $fileMetadata->webViewLink; // o webContentLink dependiendo de lo que necesites

            return $fileUrl;
        }

        //return redirect()->route('google.redirect');
    }


}