<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class StatisticsController extends Controller
{
    public function general(Request $request)
    {
        return Inertia::render('Statistics/General', [
            'title' => 'Estadísticas generales',
            'auth' => [
                'user' => $request->user(),
            ],
        ]);
    }
}