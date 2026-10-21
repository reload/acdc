<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are assigned the "api" middleware group and the "api" prefix.
|
*/

Route::post('/webhook', [WebhookController::class, 'handle']);
