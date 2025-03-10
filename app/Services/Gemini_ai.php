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
        $this->apiKey = config('services.gemini_key');
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key='.$this->apiKey;
        $this->httpClient = Http::class;
    }

    public  function GeminiConversation($HistoryChat, $prompt)
    {
        try {

            $payload = [
                "system_instruction" => [
                    "parts" => [
                        [
                            "text" => $prompt
                        ]
                    ]
                ],
                'contents' => $HistoryChat,
            ];

            $response =  $this->httpClient::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl, $payload);

            //Check if the request was successful
            if ($response->status() != 200) {
                return [
                    'error_type' => true
                ];
            }

            $geminiResponse = $response->collect();
            $geminiValidator = $geminiResponse['candidates'][0]['content']['parts'][0]['text'];
        } catch (\Throwable $th) {
            return [
                'error_type' => true
            ];
        }


        return $geminiValidator;
    }

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
                return [
                    'function_call' => $result['candidates'][0]['content']['parts'][0]['functionCall']
                ];
            } elseif (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return [
                    'content' => nl2br($result['candidates'][0]['content']['parts'][0]['text'])
                ];
            }

            return ['error' => 'No valid response from Gemini'];
        } catch (Exception $e) {
            throw new Exception('Error communicating with Gemini API: ' . $e->getMessage());
        }
    }

    public  function GeminiNerAnalysis($text, $instructions)
    {
        // Payload para enviar a Gemini
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $text,
                        ]
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
                                        "text" => $text,
                                        "context" => $instructions,
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
            return response()->json(['error' => 'Error in the request'], 401);
        }

        $geminiResponse = $response->json();

        $geminiValidator = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;
        $geminiValidator =  str_replace(["`","json"], ["",""], $geminiValidator);
        $geminiValidator = json_decode($geminiValidator, true);
//        dd($geminiValidator);

        return $geminiValidator;
    }

}
