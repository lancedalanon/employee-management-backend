<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class AiPromptService
{
    protected $user;
    protected $geminiUrl;
    
    public function __construct()
    {
        $this->user = Auth::user();
        $this->geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key={$this->user->api_key}";
    }

    public function generateResponse(string $prompt, string $data) 
    {
        // Check if API key is provided
        if (is_null($this->user->api_key)) {
            return $this->response('API key is missing. Operation aborted.', 400);
        }

        // Create the payload with the corrected structure
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt . $data
                        ]
                    ]
                ]
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_NONE',
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_NONE',
                ],
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_NONE',
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_NONE',
                ],
            ],
            'generation_config' => [
                'response_mime_type' => 'application/json',
            ],
        ];

        // Convert payload to JSON
        $jsonPayload = json_encode($payload);

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $this->geminiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonPayload),
        ]);

        // Execute cURL request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            return $this->response(null, curl_error($ch), 500);
        }

        // Close cURL session
        curl_close($ch);

        // Decode JSON response
        $data = json_decode($response, true);

        // Check for safety concerns
        if (isset($data['candidates'][0]['finishReason']) && $data['candidates'][0]['finishReason'] === 'SAFETY') {
            return $this->response(null, 'The content was flagged due to safety concerns.', 400, $data['candidates'][0]['safetyRatings']);
        }

        // Extract the text content from response and decode it
        $textContent = '';
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $textContent = $data['candidates'][0]['content']['parts'][0]['text'];
            $decodedText = json_decode($textContent, true); // Decode the JSON string
        }

        // Check for specific error message in the decoded text
        if ($decodedText && isset($decodedText['properties']['message']) && $decodedText['properties']['message'] === 'Failed to retrieve activities.' && $decodedText['properties']['response'] === '500') {
            return $this->response(null, 'Failed to retrieve activities.', 500);
        }

        // Return the decoded text content as JSON
        return $this->response($decodedText, 'Prompt generated successfully.', 200);
    }

    private function response($data, $message, $status = 200)
    {
        $response = [
            'message' => $message,
            'data' => $data,
        ];

        return Response::json($response, $status);
    }
}