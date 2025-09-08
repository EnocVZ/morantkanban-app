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
            $origin = $_SERVER['HTTP_ORIGIN'] ?? 'No origin';

            $hostValidation = [
                env('URLCONTROL'),
                env('URLPR')
            ];

            if (in_array($origin, $hostValidation)) {
                return $next($request);
            }
            return response()->json(['error' => true,"message" => "Acceso denegado"], 401);
        } catch (\Throwable $th) {
            return response()->json(['error' => true,"message" => "Acceso denegado"], 401);
        }
    }
}
