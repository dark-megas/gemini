<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use Illuminate\Http\Request;

class BotManController extends Controller
{
    public function handle(Request $request)
    {
        // Cargar el driver Web de BotMan para asegurarnos de manejar mensajes web
        DriverManager::loadDriver(WebDriver::class);

        // Obtener instancia de BotMan (proveída por el service provider de BotMan)
        $botman = app('botman');

        // Registrar comandos/escuchas
        $botman->hears('{mensaje}', function($bot, $mensaje) {
            if ($mensaje === 'hi' || $mensaje === 'hola') {
                $bot->reply('¡Hola! ¿Cómo te llamas?');
                // Aquí podríamos iniciar una conversación para preguntar el nombre
            } else {
                $bot->reply("Comando no reconocido. Escribe 'hola' para empezar.");
            }
        });

        // Iniciar la escucha del bot (procesa la entrada y envía respuesta)
        $botman->listen();
    }
}
