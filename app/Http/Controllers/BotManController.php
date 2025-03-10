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
        $this->geminiAi = $geminiAi;
        $this->disk = Storage::disk('botman');
    }

    public function handle(Request $request)
    {
        DriverManager::loadDriver(WebDriver::class);

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
                env('REDIS_PASSWORD') // <-- la pasamos aquí
            ),

            $request,

            null,

            new FileStorage(storage_path('botman'))
        );


        // Mensaje inicial con botones claros orientados a la idea del chatbot
        $botman->hears('.*', function (BotMan $bot) {
            $question = Question::create("¡Hola! 📚 Soy BookBot, ¿En qué puedo ayudarte hoy?")
                ->addButtons([
                    Button::create('Recomiéndame libros')->value('recomendacion'),
                    Button::create('Charlar de literatura')->value('charlar'),
                    Button::create('Quiero informacion de un libro libro')->value('buscar'),
                    Button::create('Salir')->value('salir'),
                ]);

            $bot->reply($question);
        });

        // Manejar las respuestas de los botones interactivos
        $botman->hears('('.implode('|',$this->cases).')', function (BotMan $bot, $payload) {
            switch ($payload) {
                case 'recomendacion':
                    $bot->startConversation(new BookRecommendationConversation($this->geminiAi));
                    break;
                case 'charlar':
                    $bot->startConversation(new CharlarConversation($this->geminiAi));
                    break;
                case 'buscar':
                    $bot->startConversation(new FindBookConversation($this->geminiAi));
                    break;
                case 'salir':
                    $bot->reply('¡Gracias por interactuar conmigo! Hasta la próxima 👋');
                    break;
            }
        });

        $botman->listen();
    }
}
