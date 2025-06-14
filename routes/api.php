<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\DataController;

Route::post('/login', [AuthController::class, 'login']);

/*le rotte sono protette dal middleware di Laravel Sanctum,
è possibile accedere agli endpoint protetti solo con token */
Route::middleware('auth:sanctum')->group(function(){
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/insert', [DataController::class, 'insert']);
    Route::post('/update', [DataController::class, 'update']);
});

