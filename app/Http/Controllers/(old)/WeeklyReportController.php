<?php

namespace App\Http\Controllers;

use App\Models\Dtr;
use App\Models\EndOfTheDayReportImage;
use App\Services\AiPromptService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class WeeklyReportController extends Controller
{
    protected $aiPromptService;

    protected $user;

    protected $prompt;

    public function __construct(AiPromptService $aiPromptService)
    {
        $this->aiPromptService = $aiPromptService;
        $this->prompt = config('prompts.weekly_report_options');
        $this->user = Auth::user();
    }

    public function showOptions()
    {
        $data = $this->showEndOfTheDayReports();

        if (! $data) {
            return Response::json([
                'message' => 'No end of the day report/s found.',
            ], 404);
        }

        $response = $this->aiPromptService->generateResponse($this->prompt, $data);

        return $response;
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
            $endOfTheDayReportsImages = EndOfTheDayReportImage::whereHas('dtr', function ($query) use ($startOfWeek, $endOfWeek) {
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

    private function showEndOfTheDayReports()
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

        if (! $endOfTheDayReports) {
            return false;
        }

        return $endOfTheDayReports;
    }
}
