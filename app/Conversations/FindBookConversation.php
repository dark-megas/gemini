<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use App\Services\Gemini_ai;
use Illuminate\Support\Facades\Http;

class FindBookConversation extends Conversation
{
    protected Gemini_ai $geminiAi;
    protected ?string $bookTitle = null;

    public function __construct(Gemini_ai $geminiAi)
    {
        $this->geminiAi = $geminiAi;
    }

    /**
     * Primer paso: preguntamos el título del libro
     */
    public function askBookTitle()
    {
        $this->ask('¿Cuál es el título del libro que estás buscando?', function($answer) {
            $this->bookTitle = trim($answer->getText());
            //add to storage
            $this->bot->userStorage()->save([
                'bookTitle' => $this->bookTitle
            ]);
            $this->searchBook();
        });
    }

    /**
     * Segundo paso: buscar el libro en Google Books.
     * Integra la llamada a la function de Gemini para que pueda decidir
     * llamar a nuestra función "searchOnlineBook" y así buscar en Google Books.
     * @throws \Exception
     */
    public function searchBook()
    {

        // Extraemos el título del libro del almacenamiento
        $this->bookTitle = $this->bot->userStorage()->get('bookTitle');

        // Construimos un prompt para Gemini
        $prompt = "El usuario desea buscar un libro con la siguiente información:\n" .
            "Título: {$this->bookTitle}\n" .
            "Usa la función de búsqueda para encontrar información en la web.";

        // Definimos la "function" que Gemini podría decidir llamar
        $functions = [
            [
                'name'        => 'searchOnlineBook',
                'description' => 'Busca un libro en Google Books con el título',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'title'          => [
                            'type'        => 'string',
                            'description' => 'Título del libro a buscar'
                        ],
                    ],
                    'required'   => ['title']
                ]
            ]
        ];

        // Llamada al método de Gemini que maneja “function calls”
        $response = $this->geminiAi->generateGeminiFunctionCall($prompt, $functions);

        // Analizamos la respuesta
        if (isset($response['function_call'])) {
            $functionCall = $response['function_call'];
            $arguments = $functionCall['args'] ?? [];

            $searchResult = $this->searchOnlineBook($arguments);
            $this->say($searchResult);

        } elseif (isset($response['content'])) {
            // Si no llamó la función, quizás devolvió un texto
            $this->say("Gemini respondió sin invocar función:");
            $this->say($response['content']);
        } else {
            // Algo no vino en un formato esperado
            $this->say("No se recibió una respuesta válida de Gemini.");
        }
    }


    protected function searchOnlineBook(array $args): string
    {
        $this->bookTitle = $this->bot->userStorage()->get('bookTitle');

        // Extraemos argumentos que Gemini decidió pasar
        $title = $args['title'] ?? $this->bookTitle;

        $apiKey = env('GOOGLE_API_KEY');

        // Construimos la URL final
        $url = "https://www.googleapis.com/books/v1/volumes?q={$title}&key={$apiKey}";

        try {
            $response = Http::get($url);
            if ($response->successful()) {
                $books = $response->json();

                if (isset($books['items']) && count($books['items']) > 0) {
                    $resultText = "Resultados de la búsqueda:\n\n";
                    foreach ($books['items'] as $index => $bookItem) {
                        $volumeInfo = $bookItem['volumeInfo'] ?? [];
                        $bookTitle = $volumeInfo['title'] ?? 'Título no disponible';
                        $bookAuthors = $volumeInfo['authors'] ?? ['Autor desconocido'];
                        $resultText .= ($index + 1).". ".$bookTitle." - ".implode(', ', $bookAuthors)."\n";
                        $resultText .= "<a target='blank' href='".$volumeInfo['infoLink']."'>Más información</a>\n\n";
                    }
                    return nl2br($resultText);
                } else {
                    return "No se encontraron resultados para la búsqueda.";
                }
            } else {
                return "La consulta a Google Books no fue exitosa. Código: ".$response->status();
            }

        } catch (\Exception $e) {
            return "Ocurrió un error al intentar buscar el libro: ".$e->getMessage();
        }
    }

    public function run()
    {
        $this->askBookTitle();
    }
}
