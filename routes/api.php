<?php

use App\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::post('/test', [IndexController::class, 'test']);