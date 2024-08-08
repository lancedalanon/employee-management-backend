<?php

namespace App\Services;

use App\Models\Dtr;
use App\Models\DtrBreak;
use Illuminate\Support\Facades\Auth;
use App\Services\User\WorkHoursService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use App\Services\CacheService;

class DtrService
{
    protected $workHoursService;
    protected $cacheService;

    public function __construct(WorkHoursService $workHoursService, CacheService $cacheService)
    {
        $this->workHoursService = $workHoursService;
        $this->cacheService = $cacheService;
    }

    public function index(int $perPage, int $page)
    {
        try {
            // Retrieve the authenticated user's ID
            $userId = Auth::id();

            // Cache the paginated DTR for the authenticated user
            $cacheKey = "user_{$userId}_dtrs_perPage_{$perPage}_page_{$page}";

            // Retrieve paginated DTR for the authenticated user
            $dtrs = $this->cacheService->rememberForever($cacheKey, function () use ($perPage, $page, $userId) {
                return Dtr::where('user_id', $userId)
                    ->whereNull('absence_date')
                    ->whereNull('absence_reason')
                    ->whereNull('absence_approved_at')
                    ->orderBy('time_in', 'desc')
                    ->paginate($perPage, ['*'], 'page', $page);
            });

            // Return the success response with DTR data
            return Response::json([
                'message' => 'DTR retrieved successfully.',
                'current_page' => $dtrs->currentPage(),
                'data' => $dtrs->items(),
                'first_page_url' => $dtrs->url(1),
                'from' => $dtrs->firstItem(),
                'last_page' => $dtrs->lastPage(),
                'last_page_url' => $dtrs->url($dtrs->lastPage()),
                'links' => $dtrs->linkCollection()->toArray(),
                'next_page_url' => $dtrs->nextPageUrl(),
                'path' => $dtrs->path(),
                'per_page' => $dtrs->perPage(),
                'prev_page_url' => $dtrs->previousPageUrl(),
                'to' => $dtrs->lastItem(),
                'total' => $dtrs->total(),
            ], 200);
        } catch (\Exception $e) {
            // Return a generic error response
            return Response::json([
                'message' => 'An error occurred while retrieving the DTRs.',
            ], 500);
        }
    }

    public function show(int $dtrId)
    {
        try {
            // Retrieve the authenticated user's ID
            $userId = Auth::id();

            // Cache the DTR for the authenticated user
            $cacheKey = "user_{$userId}_dtr_{$dtrId}";

            // Retrieve the DTR for the authenticated user
            $dtr = $this->cacheService->rememberForever($cacheKey, function () use ($dtrId, $userId) {
                return Dtr::where('dtr_id', $dtrId)
                    ->where('user_id', $userId)
                    ->whereNull('absence_date')
                    ->whereNull('absence_reason')
                    ->whereNull('absence_approved_at')
                    ->first();
            });

            // Check if the DTR was found
            if (!$dtr) {
                return Response::json([
                    'message' => 'DTR not found.'
                ], 404);
            }

            // Return the success response with the DTR data
            return Response::json([
                'message' => 'DTR retrieved successfully.',
                'data' => $dtr
            ], 200);
        } catch (\Exception $e) {
            // Return a generic error response
            return Response::json([
                'message' => 'An error occurred while retrieving the DTR.',
            ], 500);
        }
    }

    public function storeTimeIn()
    {
        try {
            // Get the authenticated user's ID
            $user = Auth::user();

            // Check if user is absent today
            $isAbsentToday = Dtr::where('user_id', $user->user_id)
                ->where('absence_date', Carbon::now())
                ->whereNotNull('absence_reason')
                ->whereNotNull('absence_approved_at')
                ->exists();

            // Send a response to the user who is absent today
            if ($isAbsentToday) {
                return Response::json([
                   'message' => 'You cannot time in again today due to being absent.'
                ], 400);
            }

            // Check if there are any previous DTR records with null time_out for the authenticated user
            $existingDtr = Dtr::where('user_id', $user->user_id)
                ->whereNull('time_out')
                ->whereNull('absence_date')
                ->whereNull('absence_reason')
                ->whereNull('absence_approved_at')
                ->exists();

            if ($existingDtr) {
                return Response::json([
                    'message' => 'You have an open time record that needs to be closed before timing in again.'
                ], 400);
            }

            // Evaluate time in based on user's shift
            $timeIn = $this->workHoursService->evaluateTimeIn($user, now());

            // Create a new DTR for the authenticated user
            $dtr = new Dtr();
            $dtr->user_id = $user->user_id;
            $dtr->time_in = $timeIn;
            $dtr->save();

            // Fetch the newly created DTR from the database
            $latestTimeIn = Dtr::where('dtr_id', $dtr->dtr_id)->where('user_id', $user->user_id)->latest()->first();

            // Return the success response with the newly created DTR data
            return Response::json([
                'message' => 'Time in recorded successfully.',
                'data' => $latestTimeIn,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while recording the time in.',
            ], 500);
        }
    }

    public function storeBreak(int $dtrId)
    {
        try {
            // Get the authenticated user's ID
            $user = Auth::user();

            // Check if user is absent today
            $isAbsentToday = Dtr::where('user_id', $user->user_id)
                ->where('absence_date', Carbon::now())
                ->whereNotNull('absence_reason')
                ->whereNotNull('absence_approved_at')
                ->exists();

            // Send a response to the user who is absent today
            if ($isAbsentToday) {
                return Response::json([
                   'message' => 'You cannot time in again today due to being absent.'
                ], 400);
            }

            // Find the DTR record
            $dtr = Dtr::with(['breaks'])
                ->where('dtr_id', $dtrId)
                ->where('user_id', $user->user_id)
                ->where('time_out', null)
                ->whereNull('absence_date')
                ->whereNull('absence_reason')
                ->whereNull('absence_approved_at')
                ->first();

            // Handle DTR record not found
            if (!$dtr) {
                return Response::json([
                    'message' => 'DTR record not found.',
                ], 404);
            }

            // Check if there is any open break (break_time set but resume_time is null)
            $hasOpenBreak = $dtr->breaks->whereNull('resume_time')->isNotEmpty();
            if ($hasOpenBreak) {
                return Response::json([
                    'message' => 'Failed to start break. You have an open break session.'
                ], 400);
            }

            // Record the break time
            $dtrBreak = new DtrBreak();
            $dtrBreak->dtr_id = $dtr->dtr_id;
            $dtrBreak->break_time = Carbon::now();
            $dtrBreak->save();

            // Retrieve the latest DtrBreak
            $latestDtrBreak = DtrBreak::where('dtr_id', $dtr->dtr_id)->latest()->first();

            // Return success response with added data
            return Response::json([
                'message' => 'Break started successfully.',
                'data' => $latestDtrBreak,
            ], 200);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while starting the break.',
            ], 500);
        }
    }

    public function storeResume(int $dtrId)
    {
        try {
            // Get the authenticated user's ID
            $user = Auth::user();

            // Check if user is absent today
            $isAbsentToday = Dtr::where('user_id', $user->user_id)
                ->where('absence_date', Carbon::now())
                ->whereNotNull('absence_reason')
                ->whereNotNull('absence_approved_at')
                ->exists();

            // Send a response to the user who is absent today
            if ($isAbsentToday) {
                return Response::json([
                   'message' => 'You cannot time in again today due to being absent.'
                ], 400);
            }

            // Find the DTR record
            $dtr = Dtr::with(['breaks'])
                ->where('dtr_id', $dtrId)
                ->where('user_id', $user->user_id)
                ->where('time_out', null)
                ->whereNull('absence_date')
                ->whereNull('absence_reason')
                ->whereNull('absence_approved_at')
                ->first();

            // Handle DTR record not found
            if (!$dtr) {
                return Response::json([
                    'message' => 'DTR record not found.',
                ], 404);
            }

            // Retrieve the latest DtrBreak with no resume_time
            $dtrBreak = $dtr->breaks()->whereNull('resume_time')->latest()->first();

            // Check if the break is found
            if (!$dtrBreak) {
                return Response::json([
                    'message' => 'Failed to resume break. No open break session found.'
                ], 400);
            }

            // Update the resume time
            $dtrBreak->resume_time = Carbon::now();
            $dtrBreak->save();

            // Retrieve the latest DtrBreak
            $latestDtrResume = DtrBreak::where('dtr_id', $dtr->dtr_id)->latest()->first();

            // Return success response with added data
            return Response::json([
                'message' => 'Break resumed successfully.',
                'data' => $latestDtrResume,
            ], 200);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while resuming the break.',
            ], 500);
        }
    }

    public function storeTimeOut(string $endOfTheDayReport, int $dtrId, ?array $uploadedImages)
    {
        try {
            // Get the authenticated user's ID
            $user = Auth::user();

            // Check if user is absent today
            $isAbsentToday = Dtr::where('user_id', $user->user_id)
                ->where('absence_date', Carbon::now())
                ->whereNotNull('absence_reason')
                ->whereNotNull('absence_approved_at')
                ->exists();

            // Send a response to the user who is absent today
            if ($isAbsentToday) {
                return Response::json([
                   'message' => 'You cannot time in again today due to being absent.'
                ], 400);
            }

            // Find the DTR record
            $dtr = Dtr::where('user_id', $user->user_id)
                ->where('dtr_id', $dtrId)
                ->whereNull('absence_date')
                ->whereNull('absence_reason')
                ->whereNull('absence_approved_at')
                ->first();

            // Handle DTR record not found
            if (!$dtr) {
                return Response::json([
                    'message' => 'DTR record not found.',
                ], 404);
            }

            // Check if the DTR record is found and if it's not already timed out
            if ($dtr->time_out) {
                return Response::json([
                    'message' => 'Failed to time-out. Record has already been timed out.'
                ], 400);
            }

            // Check for any open breaks
            if ($dtr->breaks()->whereNull('resume_time')->exists()) {
                return Response::json([
                    'message' => 'You have an open break that needs to be resumed before timing out.'
                ], 400);
            }

            // Evaluate time out
            $timeOut = $this->workHoursService->evaluateTimeOut($user, now());
            $timeIn = Carbon::parse($dtr->time_in);

            // Check if the user has enough worked hours to time out
            if (!$this->workHoursService->findTimeInTimeOutDifference($user, $dtr, $timeIn, $timeOut)) {
                return Response::json([
                    'message' => 'Insufficient worked hours. You need to work at least 8 hours before timing out for full-time or 4 hours for part-time.'
                ], 400);
            }

            // Update the DTR record
            $dtr->time_out = $timeOut;
            $dtr->end_of_the_day_report = $endOfTheDayReport;
            $dtr->save();

            // Handle image uploads
            $uploadedImagesPaths = $this->handleEndOfTheDayReportImages($uploadedImages);

            if ($uploadedImagesPaths === false) {
                return Response::json([
                    'message' => 'You can only upload up to 4 images.'
                ], 400);
            }

            // Save the images
            foreach ($uploadedImagesPaths as $uploadedImagePath) {
                // Create a new EndOfTheDayReportImage instance for each path
                $dtr->endOfTheDayReportImages()->create([
                    'end_of_the_day_report_image' => $uploadedImagePath
                ]);
            }

            // Return success response with updated DTR data
            $latestTimeOut = Dtr::where('dtr_id', $dtr->dtr_id)->where('user_id', $user->user_id)->latest()->first();

            return Response::json([
                'message' => 'Time out recorded successfully.',
                'data' => $latestTimeOut,
            ], 200);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while recording the time out.',
            ], 500);
        }
    }

    protected function handleEndOfTheDayReportImages(?array $images)
    {
        // Check if the number of images exceeds the limit
        if (count($images) > 4) {
            return false;
        }

        $uploadedImages = [];
        foreach ($images as $image) {
            // Save the images and add to the array
            $path = $image->store('end_of_the_day_report_images', 'public');
            $uploadedImages[] = $path;
        }

        return $uploadedImages;
    }
}
