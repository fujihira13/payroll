<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('SELECT 1');

            return response()->json([
                'status' => 'ok',
                'app' => config('app.name'),
                'database' => 'ok',
            ]);
        } catch (Throwable) {
            return response()->json([
                'status' => 'error',
                'app' => config('app.name'),
                'database' => 'error',
            ], 503);
        }
    }
}
