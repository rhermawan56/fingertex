<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\LogApiAccess;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('webhook', [WebhookController::class, 'receive'])->middleware(LogApiAccess::class);
Route::get('registermachine/{cloudid}', [WebhookController::class, 'registermachine']);
Route::get('cron/{id}/{day}', [WebhookController::class, 'cron']);
Route::get('cronlocal/{day}', [WebhookController::class, 'cronlocal']);
Route::get('cronlocal2/{day}', [WebhookController::class, 'cronlocal2']);
Route::get('queue/{key}/{day}', [WebhookController::class, 'queue']);
Route::get('worker', [WebhookController::class, 'worker']);
Route::get('tes', [WebhookController::class, 'tes']);