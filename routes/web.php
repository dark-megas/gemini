<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotManController;



Route::get('/', function () {
    return view('welcome');
});

Route::match(['get', 'post'], '/botman', [BotManController::class, 'handle']);

Route::get('/botman/chat', function () {
    return view('botman'); // renderiza resources/views/botman.blade.php
});
