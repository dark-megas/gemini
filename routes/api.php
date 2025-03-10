<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BotManController;


Route::match(['get', 'post'], '/botman', [BotManController::class, 'handle']);

