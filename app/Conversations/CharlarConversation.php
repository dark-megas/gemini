<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use App\Services\Gemini_ai;

class CharlarConversation extends Conversation
{
    protected Gemini_ai $geminiAi;
    protected array $history = [];

    public function __construct(Gemini_ai $geminiAi)
    {
        $this->geminiAi = $geminiAi;
    }

    public function chat()
    {
        $this->ask(count($this->history) === 0 ? '¿De qué te gustaría hablar?' : '...', function($answer) {
            $mensajeUsuario = $answer->getText();


            // Instruction to exit the conversation
            $instruction = "Apartir del texto del usuario genera json el del ejemplo:".PHP_EOL;
            $instruction .= json_encode([
                'close_session' => 'true|false',
            ]).PHP_EOL;
            $instruction .="No tienes permitido responder con otra cosa que no sea un json".PHP_EOL;

            $ner = $this->geminiAi->GeminiNerAnalysis($mensajeUsuario, $instruction);

            if (isset($ner['close_session']) && $ner['close_session'] === "true") {
                return $this->say('¡Fue un placer hablar contigo! Hasta pronto. 👋');
            }

            $this->history[] = [
                'role' => 'user',
                'parts' => [['text' => $mensajeUsuario]],
            ];
            $prompt = "Eres un asistente conversacional útil y amigable.".PHP_EOL;
            $prompt .= "Esta conversación es solo para hablar de literatura";

            $respuestaGemini = $this->geminiAi->GeminiConversation($this->history, $prompt);

            $respuestaGeminiFormateada = nl2br($respuestaGemini);

            $this->history[] = [
                'role' => 'model',
                'parts' => [['text' => $respuestaGeminiFormateada]],
            ];

            $this->say($respuestaGeminiFormateada);

            // Recursión para mantener la conversación activa
            $this->chat();
        });
    }

    public function run()
    {
        $this->history = [];
        $this->chat();
    }
}
