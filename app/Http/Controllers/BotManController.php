<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Storages\Drivers\FileStorage;
use BotMan\Drivers\Web\WebDriver;
use Illuminate\Http\Request;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Cache\RedisCache;
use App\Services\Gemini_ai;
use App\Conversations\BookRecommendationConversation;
use App\Conversations\CharlarConversation;
use App\Conversations\FindBookConversation;
use Illuminate\Support\Facades\Storage;

class BotManController extends Controller
{
    protected Gemini_ai $geminiAi;
    private $disk;

    protected array $cases = [
        'recomendacion',
        'charlar',
        'buscar',
        'salir'
    ];

    public function __construct(Gemini_ai $geminiAi)
    {
        // Inyección de la clase que conecta con la API de Gemini
        $this->geminiAi = $geminiAi;
        // Definimos un disco específico para almacenar la información del bot
        $this->disk = Storage::disk('botman');
    }

    public function handle(Request $request)
    {
        // Carga del driver Web para escuchar interacciones por navegador
        DriverManager::loadDriver(WebDriver::class);

        // Creación de la instancia de BotMan con:
        // - Configuración de caché de conversaciones y usuarios.
        // - Uso de Redis para almacenar y recuperar estados.
        // - Un driver FileStorage para persistir datos localmente
        $botman = BotManFactory::create(
            [
                'config' => [
                    'conversation_cache_time' => 950,
                    'user_cache_time' => 950,
                ]
            ],
            new RedisCache(
                env('REDIS_HOST'),
                env('REDIS_PORT'),
                env('REDIS_PASSWORD')
            ),
            $request,
            null,
            new FileStorage(storage_path('botman'))
        );

        // Capturamos cualquier mensaje (con la expresión '.*') y devolvemos un mensaje inicial
        // con botones que guían al usuario a las distintas funcionalidades
        $botman->hears('.*', function (BotMan $bot) {
            $question = Question::create("¡Hola! 📚 Soy BookBot, ¿En qué puedo ayudarte hoy?")
                ->addButtons([
                    Button::create('Recomiéndame libros')->value('recomendacion'),
                    Button::create('Charlar de literatura')->value('charlar'),
                    Button::create('Quiero informacion de un libro')->value('buscar'),
                    Button::create('Salir')->value('salir'),
                ]);

            $bot->reply($question);
        });

        // Maneja la acción según el botón presionado por el usuario
        // Inicia la conversación correspondiente o da por finalizada la interacción
        $botman->hears('('.implode('|',$this->cases).')', function (BotMan $bot, $payload) {
            switch ($payload) {
                case 'recomendacion':
                    // Conversación para recomendar libros
                    $bot->startConversation(new BookRecommendationConversation($this->geminiAi));
                    break;
                case 'charlar':
                    // Conversación para charlar de literatura
                    $bot->startConversation(new CharlarConversation($this->geminiAi));
                    break;
                case 'buscar':
                    // Conversación para buscar libros específicos
                    $bot->startConversation(new FindBookConversation($this->geminiAi));
                    break;
                case 'salir':
                    // Finaliza la interacción
                    $bot->reply('¡Gracias por interactuar conmigo! Hasta la próxima \uD83D\uDC4B');
                    break;
            }
        });

        // Escucha los mensajes entrantes y procesa las conversaciones
        $botman->listen();
    }
}
