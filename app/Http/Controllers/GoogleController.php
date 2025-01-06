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
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;

class GoogleController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setAuthConfig(storage_path('app/google/credentials.json'));
        
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->addScope(Drive::DRIVE_FILE);
        $this->client->addScope(Google_Service_Drive::DRIVE);
        $this->client->addScope(Google_Service_Drive::DRIVE_METADATA_READONLY);

        
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setRedirectUri(route('google.callback'));
    }

    public function redirectToGoogle()
    {
        $this->client->setPrompt('select_account');
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

    private function refreshGoogleToken()
    {
        $tokenData = json_decode(file_get_contents(storage_path('app/google/google-refresh-token.json')), true);
        $refreshToken = $tokenData['refresh_token'];

        $token = $tokenData['google_token'];
        if ($token) {
            $this->client->setAccessToken($token);

            if ($this->client->isAccessTokenExpired()) {
                $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                $newToken = $this->client->getAccessToken();

                if (isset($newToken['refresh_token'])) {
                    file_put_contents(storage_path('app/google/google-refresh-token.json'), json_encode([
                        'refresh_token' => $newToken['refresh_token'],
                        'google_token' => $newToken,
                    ]));
                }
            }
            return true; // Token refreshed successfully
        } else {
            return false;
        }
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
             $this->refreshGoogleToken(); // Actualiza y valida el token

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
        } catch (\Throwable $th) {
            $response['message'] = $th->getMessage();
        }

        return response()->json($response);
    }

    public function uploadFile($proyectFolder, Request $request, $folderId)
    {
        

        try {
            $token = $this->refreshGoogleToken();
            if(!$token)new Exception("Se necesita sesión de Google");

            $driveService = new Google_Service_Drive($this->client);

            // Crear la carpeta principal
            $parentFolderName = 'morantkanban';
            $parentFolderId = $this->createFolder($driveService, $parentFolderName);

            // Crear la subcarpeta dentro de la carpeta principal
            #$subFolderId = $this->createFolder($driveService, $proyectFolder, $parentFolderId);

            // Subir el archivo a la subcarpeta
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $request->file('file')->getClientOriginalName(),
                'parents' => [$folderId]
            ]);
            $content = file_get_contents($request->file('file')->path());

            $file = $driveService->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $request->file('file')->getMimeType(),
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            $fileId = $file->id;

            // Cambiar permisos para hacerlo público
            $permission = new Drive\Permission();
            $permission->setType('anyone');
            $permission->setRole('reader');
            $driveService->permissions->create($fileId, $permission);

            // Obtener el enlace público
            $fileMetadata = $driveService->files->get($fileId, ['fields' => 'webViewLink, webContentLink']);
            $fileUrl = $fileMetadata->webViewLink; // o webContentLink dependiendo de lo que necesites

            return ["error"=>false,'fileId' => $fileUrl, 'message' => 'Archivo subido con éxito.'];
        } catch (\Exception $th) {
            return ["error"=>true,'message' => "Problemas al cargar el archivo a drive"];
        }
    }

    private function createFolder($driveService, $folderName, $parentFolderId = null)
    {
        // Verificar si la carpeta ya existe
        $query = "mimeType='application/vnd.google-apps.folder' and name='$folderName' and trashed=false";
        if ($parentFolderId) {
            $query .= " and '$parentFolderId' in parents";
        }
        $response = $driveService->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)'
        ]);
        $files = $response->files;

        // Crear la carpeta si no existe
        if (count($files) > 0) {
            return $files[0]->id;
        } else {
            $folderMetadata = new Google_Service_Drive_DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => $parentFolderId ? [$parentFolderId] : []
            ]);
            $folder = $driveService->files->create($folderMetadata, [
                'fields' => 'id'
            ]);
            return $folder->id;
        }
    }
    
    

public function listFolders($parentFolderId)
{
    try {
       #$parentFolderId = "1sIqim-BN4gkoPSbvUb5etbYOC0fa9n8N";
        // Refrescar el token de Google y autenticar al cliente
        $token = $this->refreshGoogleToken();
        if (!$token) throw new Exception("Se necesita sesión de Google");

        // Inicializar el servicio de Google Drive
        $service = new Google_Service_Drive($this->client);

        if($parentFolderId == "default"){
            // Crear la carpeta principal
            $parentFolderName = 'defaultkanbanfolder';
            $parentFolderId = $this->createFolder($service, $parentFolderName);
        }
        

        // Consulta para buscar carpetas con el padre especificado
        $query = "mimeType = 'application/vnd.google-apps.folder' and trashed = false and '$parentFolderId' in parents";

        // Parámetros de consulta
        $parameters = [
            'q' => $query,
            'fields' => 'nextPageToken, files(id, name)',
            'includeItemsFromAllDrives' => true,
            'supportsAllDrives' => true,
        ];

        $folders = [];
        $pageToken = null;

        // Manejar la paginación en caso de que haya muchas carpetas
        do {
            $parameters['pageToken'] = $pageToken;
            $results = $service->files->listFiles($parameters);

            foreach ($results->getFiles() as $folder) {
                $folders[] = [
                    'id' => $folder->getId(),
                    'name' => $folder->getName(),
                ];
            }

            $pageToken = $results->getNextPageToken();
        } while ($pageToken);

        return response()->json([
            'error' => false,
            'data' => $folders,
        ]);

    } catch (\Google_Service_Exception $e) {
        // Manejo de errores específicos de la API de Google
        $error = json_decode($e->getMessage(), true);
        $errorMessage = $error['error']['message'] ?? 'Error desconocido en Google Drive.';
        return response()->json(['error' => true, 'message' => "Error en Google API: $errorMessage"]);

    } catch (\Exception $e) {
        // Manejo de errores generales
        return response()->json(['error' => true, 'message' => 'Error: ' . $e->getMessage()]);
    }
}


}