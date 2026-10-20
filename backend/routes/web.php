<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — disabled (API-only application)
|--------------------------------------------------------------------------
| This application serves JSON only. All endpoints are under /api/*.
| Direct web route access returns a clear JSON "not found" response.
*/

Route::fallback(fn () => response()->json([
    'message' => 'This is a JSON API. All endpoints are under /api/',
    'docs'    => 'https://github.com/CodeMasterLYR/lyr-pkg-paymongo-starter',
], 404));
