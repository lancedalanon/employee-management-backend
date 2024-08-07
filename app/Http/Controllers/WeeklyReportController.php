<?php

namespace App\Http\Controllers;

use App\Models\Dtr;
use App\Models\EndOfTheDayReportImage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WeeklyReportController extends Controller
{
    protected $user;
    protected $geminiUrl;
    protected $basePrompt;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key={$this->user->api_key}";
        $this->basePrompt = 'Process the data into a JSON structure with two options, here is only an example:
            Data:
            [
            {
                end_of_the_day_report: "I did a text report today regarding the project."
            },
            {
                end_of_the_day_report: "I did a programming course that elevated my skills further and written a text report for today\'s progress."
            }
            ]
            
            Desired output format using this JSON schema:
        
            { "type": "object",
                "properties": {
                option1: "Project report text has been completed.",
                option2: "Studied programming which elevated skills.",
                }
            }

            Please don`t mind the example above, the array should be empty like this:
            { "type": "object",
                "properties": {
                }
            }
            
            Focus on extracting key activities and goals from the daily reports. 
            Combine similar activities into a single option. 
            Highlight achieved goals. 
            Avoid repetition in all options.
            Please also keep each option to be one sentence only.
            All activities must be separated by splitting them into options options not commas.
            Make it into an objective view without first person view as a person of reference.
            Refrain from using articles from the beginning (a, an, the, etc.).
            You should start with the data:';
    }

    public function showOptions()
     {
        $data = $this->showEndOfTheDayReports();

        if(!$data) {
            return Response::json([
                'message' => 'No end of the day report/s found.',
            ], 404);
        }

        // Create the payload with the corrected structure
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $this->basePrompt . $data
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

    protected function showEndOfTheDayReports() 
    {
        // Get the current date
        $now = Carbon::now();
        
        // Determine the start of the week (Sunday)
        $startOfWeek = $now->startOfWeek(Carbon::SUNDAY)->toDateString();
        
        // Determine the end of the week (Saturday)
        $endOfWeek = $now->endOfWeek(Carbon::SATURDAY)->toDateString();

        // Fetch the end of the day reports for the current week
        $endOfTheDayReports = Dtr::where('user_id', $this->user->user_id)
            ->whereBetween('time_in', [$startOfWeek, $endOfWeek])
            ->get(['end_of_the_day_report']);

        if (!$endOfTheDayReports) {
            return false;
        }

        return $endOfTheDayReports;
    }

    public function showEndOfTheDayReportImages() 
    {
        try {
            // Get the current date
            $now = Carbon::now();
                    
            // Determine the start of the week (Sunday)
            $startOfWeek = $now->startOfWeek(Carbon::SUNDAY)->toDateString();

            // Determine the end of the week (Saturday)
            $endOfWeek = $now->endOfWeek(Carbon::SATURDAY)->toDateString();

            // Fetch the end of the day reports for the current week
            $endOfTheDayReportsImages = EndOfTheDayReportImage::
                whereHas('dtr', function ($query) use ($startOfWeek, $endOfWeek) {
                    $query->whereBetween('time_in', [$startOfWeek, $endOfWeek]);
                })->get(['end_of_the_day_report_image']);

            return Response::json([
                'message' => 'End of the day report images retrieved successfully.',
                'data' => $endOfTheDayReportsImages,
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to retrieve end of the day report images.',
            ], 500);
        }
    }
}
