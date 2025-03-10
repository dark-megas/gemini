<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class Gemini_ai
{
    protected mixed $apiKey;
    protected string $apiUrl;
    protected mixed $httpClient;

    public function __construct()
    {
        // Obtiene la clave de la configuración de servicios (config/services.php)
        $this->apiKey = config('services.gemini_key');
        // URL base de la API de Gemini en modo flash
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key='.$this->apiKey;
        // Cliente HTTP para realizar peticiones
        $this->httpClient = Http::class;
    }

    /**
     * Método para enviar una conversación a Gemini
     * @param $HistoryChat array Historial de la conversación (roles y mensajes)
     * @param $prompt string Instrucción o pregunta para generar respuesta
     * @return mixed Texto generado por Gemini o array de error
     */
    public function GeminiConversation($HistoryChat, $prompt)
    {
        try {
            $payload = [
                "system_instruction" => [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ],
                'contents' => $HistoryChat,
            ];

            // Envía una solicitud POST a la API con encabezados JSON
            $response =  $this->httpClient::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl, $payload);

            // Verifica si la solicitud fue exitosa
            if ($response->status() != 200) {
                return ['error_type' => true];
            }

            // Toma la respuesta, la convierte a una colección y extrae el texto
            $geminiResponse = $response->collect();
            $geminiValidator = $geminiResponse['candidates'][0]['content']['parts'][0]['text'];
            return $geminiValidator;
        } catch (\Throwable $th) {
            return ['error_type' => true];
        }
    }

    /**
     * Método para llamar a una función desde Gemini
     * @param $prompt string Mensaje o instrucción
     * @param $functions array Declaraciones de funciones para que Gemini las use
     * @return array Respuesta con la llamada a la función o el contenido generado
     */
    public function generateGeminiFunctionCall($prompt, array $functions): array
    {
        $data = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'tools' => [
                [
                    'function_declarations' => $functions
                ]
            ]
        ];

        try {
            $response = $this->httpClient::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl, $data);

            $result = $response->collect();

            if (isset($result['candidates'][0]['content']['parts'][0]['functionCall'])) {
                // Devuelve la llamada a la función
                return [
                    'function_call' => $result['candidates'][0]['content']['parts'][0]['functionCall']
                ];
            } elseif (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                // Devuelve el texto si no se generó una función
                return [
                    'content' => nl2br($result['candidates'][0]['content']['parts'][0]['text'])
                ];
            }

            return ['error' => 'No valid response from Gemini'];
        } catch (Exception $e) {
            throw new Exception('Error communicating with Gemini API: ' . $e->getMessage());
        }
    }

    /**
     * Método para analizar entidades nombradas en un texto
     * @param $text string Texto a analizar
     * @param $instructions string Instrucciones o contexto adicional
     * @return array Array con información de las entidades extraídas
     */
    public function GeminiNerAnalysis($text, $instructions)
    {
        // Payload para enviar a Gemini
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $text]
                    ]
                ],
                [
                    'role' => 'model',
                    'parts' => [
                        [
                            'functionCall' => [
                                'name' => 'analyze_ner',
                                'args' => [
                                    'context' => $instructions,
                                    'text' => $text
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'role' => 'function',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => 'analyze_ner',
                                'response' => [
                                    'name' => 'analyze_ner',
                                    'content' => [
                                        'text' => $text,
                                        'context' => $instructions,
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'tools' => [
                'functionDeclarations' => [
                    [
                        'name' => 'analyze_ner',
                        'description' => 'Crear un JSON con la información de las entidades nombradas en el texto',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'context' => [
                                    'type' => 'STRING',
                                    'description' => 'El contexto en el que se analiza el texto'
                                ],
                                'text' => [
                                    'type' => 'STRING',
                                    'description' => 'El texto a analizar'
                                ]
                            ],
                            'required' => ['text']
                        ]
                    ]
                ]
            ]
        ];

        // Solicitud a Gemini
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->withOptions([
            'verify' => false,
        ])->post($this->apiUrl, $payload);

        // Verificar si la solicitud fue exitosa
        if ($response->status() != 200) {
            return ['error' => 'Error in the request'];
        }

        $geminiResponse = $response->json();

        // Extraemos el texto devuelto (puede venir en formato JSON)
        $geminiValidator = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;
        $geminiValidator = str_replace(["`","json"], ["",""], $geminiValidator);
        $geminiValidator = json_decode($geminiValidator, true);

        return $geminiValidator;
    }
}
