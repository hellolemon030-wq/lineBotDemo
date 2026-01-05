<?php

use App\Http\Controllers\IndexController;
use App\Http\Controllers\LineWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('test',[
    IndexController::class,'test'
    //LineWebhookController::class, 'webhook2'  //必然出错。。。
]);
Route::get('msg',[
    IndexController::class,'test'
    //LineWebhookController::class, 'webhook2'
]);


Route::get('webhook/{botKey?}',[
    LineWebhookController::class,'webhook2'
]);

// routes/web.php
Route::post('/webhook/{botKey?}', [LineWebhookController::class, 'webhook'])
->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
// routes/web.php
Route::get('/webhook2', [LineWebhookController::class, 'webhook2'])
->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
// routes/web.php
Route::get('/test', [IndexController::class, 'test'])
->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

