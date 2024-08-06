<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WeeklyReportController extends Controller
{
    protected $basePrompt = 'Process the following data into a JSON structure with two options:
        Data:
        [
        {
            dtr_id: 1,
            end_of_the_day_report: "I did a text report today regarding the project."
        },
        {
            dtr_id: 2,
            end_of_the_day_report: "I did a programming course that elevated my skills further and written a text report for today\'s progress."
        }
        ]
        
        Desired output format using this JSON schema:
    
        { "type": "object",
            "properties": {
            option1: "Summary of activities without repetition",
            option2: "Summary of goals achieved"
            }
        }
        
        Focus on extracting key activities and goals from the daily reports. 
        Combine similar activities into a single option. 
        Highlight achieved goals. 
        Avoid repetition in all options.
        
        Do these with the data below:';

    public function showUserEndOfTheDayReports(Request $request)
     {
        // Validate the incoming request data to ensure 'prompt' is a string
        $request->validate([
            'prompt' => 'required|string',
        ]);

        // Get authenticated user's API key
        $user = Auth::user();

        // Google Gemini API URL
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $user->api_key;

        // Retrieve the user-provided prompt from the request and ensure it's a string
        $userPrompt = (string) $request->input('prompt');

        // Create the payload with the corrected structure
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $this->basePrompt . $userPrompt
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
        curl_setopt($ch, CURLOPT_URL, $url);
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
            return Response::json(['error' => curl_error($ch)], 500);
        }

        // Close cURL session
        curl_close($ch);

        // Decode JSON response
        $data = json_decode($response, true);

        // Check for safety concerns
        if (isset($data['candidates'][0]['finishReason']) && $data['candidates'][0]['finishReason'] === 'SAFETY') {
            return Response::json([
                'error' => 'The content was flagged due to safety concerns.',
                'safetyRatings' => $data['candidates'][0]['safetyRatings']
            ], 400);
        }

        // Extract the text content from response and decode it
        $textContent = '';
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $textContent = $data['candidates'][0]['content']['parts'][0]['text'];
            $decodedText = json_decode($textContent, true); // Decode the JSON string
        }

        // Return the decoded text content as JSON
        return Response::json(['data' =>  $decodedText], 200);
    }
}
