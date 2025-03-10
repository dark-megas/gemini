<?php

namespace App\Conversations;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;

class FlowAsk extends Conversation
{
    public function run()
    {
        $this->askReason();
    }

    public function askReason()
    {
        $this->ask('¿Cuál es tu nombre?', function (Answer $answer) {
            $this->say('¡Encantado de conocerte, ' . $answer->getText() . '!');
            $this->askAge();
        });
    }

    public function askAge()
    {
        $this->ask('¿Cuántos años tienes?', function (Answer $answer) {
            $this->say('¡Interesante! Tienes ' . $answer->getText() . ' años.');
            $this->askGender();
        });
    }

    public function askGender()
    {
        $this->ask('¿Cuál es tu género?', function (Answer $answer) {
            $this->say('¡Entendido! Eres ' . $answer->getText() . '.');
            $this->askLocation();
        });
    }

    public function askLocation()
    {
        $this->ask('¿Dónde vives?', function (Answer $answer) {
            $this->say('¡Gracias! Vives en ' . $answer->getText() . '.');
            $this->askEmail();
        });
    }

    public function askEmail()
    {
        $this->ask('¿Cuál es tu correo electrónico?', function (Answer $answer) {
            $this->say('¡Perfecto! Tu correo es ' . $answer->getText() . '.');
            $this->askPhone();
        });
    }

    public function askPhone()
    {
        $this->ask('¿Cuál es tu número de teléfono?', function (Answer $answer) {
            $this->say('¡Gracias! Tu número es ' . $answer->getText() . '.');
            $this->askHobbies();
        });
    }

    public function askHobbies()
    {
        $this->ask('¿Cuáles son tus pasatiempos?', function (Answer $answer) {
            $this->say('¡Interesante! Tus pasatiempos son ' . $answer->getText() . '.');
        });
        $this->say('¡Gracias por responder a mis preguntas!');
    }

}
