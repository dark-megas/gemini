<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use App\Services\Gemini_ai;

class BookRecommendationConversation extends Conversation
{
    protected Gemini_ai $geminiAi;
    protected array $history;
    protected string $genre;
    protected string $author;

    public function __construct(Gemini_ai $geminiAi)
    {
        $this->geminiAi = $geminiAi;
        $this->history = [];
    }

    public function askGenre()
    {
        $this->ask('¿Cuál es tu género literario favorito? (fantasía, ciencia ficción, romance, etc.)', function($answer) {
            $this->genre = $answer->getText();

            $this->history[] = [
                'role' => 'user',
                'parts' => [['text' => $answer = $this->genre]]
            ];

            $this->askAuthorPreference();
        });
    }

    public function askAuthorPreference()
    {
        $this->ask('¿Tienes algún autor preferido o te gustaría descubrir nuevos autores?', function($answer) {
            $this->author = $answer->getText();

            $this->history[] = [
                'role' => 'user',
                'parts' => [['text' => $this->author]]
            ];

            $this->recommendBooks();
        });
    }

    public function recommendBooks()
    {
        $prompt = "Recomienda tres libros del género {$this->genre}.";

        if (strtolower($this->author) !== 'no' && strtolower($this->author) !== 'nuevos') {
            $prompt .= " Similar al estilo del autor: {$this->author}.";
        }

        // Agrega el prompt final como instrucción del sistema
        $response = $this->geminiAi->GeminiConversation($this->history, $prompt);

        if (isset($response['error_type'])) {
            $this->say("Lo siento, hubo un error al consultar a Gemini. Intenta de nuevo.");
        } else {
            // Añade la respuesta al historial
            $this->history[] = [
                'role' => 'model',
                'parts' => [['text' => $response]],
            ];

            $this->say("✨ Aquí tienes algunas recomendaciones:");
            $this->say(nl2br($response));
            $this->say('¡Espero que disfrutes leyendo! 📖');
        }
    }

    public function run()
    {
        $this->askGenre();
    }
}
