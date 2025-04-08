<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;


class ValidateApiExternal
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $authHeader = $request->header('Authorization');

            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return response()->json(['error' => 'Acceso denegado'], 401);
            }

            $token = substr($authHeader, 7);
            $desencriptado = Crypt::decryptString($token);
            $token = json_decode($desencriptado, true);
            if (is_null($token)) {
                return response()->json(['error' => 'Acceso denegado'], 401);
            }

            if ($token['application'] == "opinometro" && $token['code'] == "APP-OP-KANBAN") {
                return $next($request);
                
            }
            return response()->json(['error' => true,"message" => "Acceso denegado"], 401);
        } catch (\Throwable $th) {
            return response()->json(['error' => true,"message" => "Acceso denegado"], 401);
        }
    }
}
