<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\EndOfTheDayReportImage;
use Illuminate\Support\Facades\Auth;
use App\Services\User\UserRoleService;
use App\Services\User\WorkHoursService;
use Carbon\Carbon;

class DtrController extends Controller
{
    protected $workHoursService;

    /**
     * DtrController constructor.
     *
     * @param WorkHoursService $workHoursService
     */
    public function __construct(WorkHoursService $workHoursService)
    {
        $this->workHoursService = $workHoursService;
    }

    /**
     * Gets the paginated DTR entries for the authenticated user.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with paginated DTR entries.
     */
    public function getDtr(Request $request)
    {
        try {
            // Get the authenticated user's ID
            $userId = Auth::id();

            // Get 'page' parameter from the request, with a default value of 1
            $page = $request->input('page', 1); // Default to 1 if not provided

            // Hardcoded 'per_page' value
            $perPage = 10;

            // Retrieve DTR entries for the authenticated user, with pagination
            $dtrs = Dtr::with(['breaks'])
                ->where('user_id', $userId)
                ->orderBy('time_in', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Return the paginated DTR entries as a JSON response
            return response()->json([
                'success' => true,
                'message' => 'DTR entries retrieved successfully.',
                'data' => $dtrs
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur during the process
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the DTR entries.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gets the DTR entry for the authenticated user by DTR ID.
     *
     * @param int $dtrId The ID of the DTR entry.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with the DTR entry.
     */
    public function getDtrById($dtrId)
    {
        try {
            // Get the authenticated user's ID
            $userId = Auth::id();

            // Retrieve the DTR entry for the authenticated user by DTR ID
            $dtr = Dtr::with(['breaks'])
                ->where('id', $dtrId)
                ->where('user_id', $userId)
                ->orderBy('time_in', 'desc')
                ->first();

            // Check if the DTR entry was found
            if (!$dtr) {
                return response()->json([
                    'success' => false,
                    'message' => 'DTR entry not found.'
                ], 404);
            }

            // Return the DTR entry as a JSON response
            return response()->json([
                'success' => true,
                'message' => 'DTR entry retrieved successfully.',
                'data' => $dtr
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur during the process
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the DTR entry.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Records the time in for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with a success message and the newly created DTR entry, or an error message.
     */
    public function timeIn()
    {
        try {
            // Get the authenticated user's ID and object
            $userId = Auth::id();
            $user = Auth::user();

            // Check if there are any previous DTR records with null time_out for the authenticated user
            $existingDtr = Dtr::where('user_id', $userId)
                ->whereNull('time_out')
                ->exists();

            if ($existingDtr) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have an open time record that needs to be closed before timing in again.'
                ], 400);
            }

            // Evaluate time in based on user's shift
            $timeIn = $this->workHoursService->evaluateTimeIn($user, now());

            // Create a new DTR entry for the authenticated user
            $dtr = new Dtr();
            $dtr->user_id = $userId;
            $dtr->time_in = $timeIn;
            $dtr->save();

            // Fetch the newly created DTR entry from the database
            $latestTimeIn = Dtr::where('id', $dtr->id)->where('user_id', $userId)->latest()->first();

            // Return the success response with the newly created DTR entry data
            return response()->json([
                'success' => true,
                'message' => 'Time in recorded successfully.',
                'data' => $latestTimeIn,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur during the process
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while recording the time in.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Records the start of a break for the authenticated user.
     *
     * @param int $dtrId The ID of the DTR record to associate with the break.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with a success message or an error message.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the DTR record is not found.
     */
    public function break($dtrId)
    {
        try {
            // Get the authenticated user's ID
            $userId = Auth::id();

            // Find the DTR record
            $dtr = Dtr::with(['breaks'])
                ->where('id', $dtrId)
                ->where('user_id', $userId)
                ->where('time_out', null)
                ->firstOrFail();

            // Check if there is any open break (break_time set but resume_time is null)
            $hasOpenBreak = $dtr->breaks->whereNull('resume_time')->isNotEmpty();
            if ($hasOpenBreak) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start break. You have an open break session.'
                ], 400);
            }

            // Record the break time
            $dtrBreak = new DtrBreak();
            $dtrBreak->dtr_id = $dtr->id;
            $dtrBreak->break_time = Carbon::now();
            $dtrBreak->save();

            // Retrieve the latest DtrBreak entry
            $latestDtrBreak = DtrBreak::where('dtr_id', $dtr->id)->latest()->first();

            // Return success response with added data
            return response()->json([
                'success' => true,
                'message' => 'Break started successfully.',
                'data' => $latestDtrBreak,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle DTR record not found
            return response()->json([
                'success' => false,
                'message' => 'DTR record not found.',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while starting the break.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resumes the break for the authenticated user.
     *
     * @param int $dtrId The ID of the DTR record to update.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with a success message or an error message.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the DTR record or the latest break is not found.
     */
    public function resume($dtrId)
    {
        try {
            // Get the authenticated user's ID
            $userId = Auth::id();

            // Find the DTR record
            $dtr = Dtr::with(['breaks'])
                ->where('id', $dtrId)
                ->where('user_id', $userId)
                ->where('time_out', null)
                ->firstOrFail();

            // Retrieve the latest DtrBreak entry with no resume_time
            $dtrBreak = $dtr->breaks()->whereNull('resume_time')->latest()->first();

            // Check if the break entry is found
            if (!$dtrBreak) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to resume break. No open break session found.'
                ], 400);
            }

            // Update the resume time
            $dtrBreak->resume_time = Carbon::now();
            $dtrBreak->save();

            return response()->json([
                'success' => true,
                'message' => 'Break resumed successfully.',
                'data' => $dtrBreak,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle DTR record not found
            return response()->json([
                'success' => false,
                'message' => 'DTR record not found.',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resuming the break.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Records the time out for the authenticated user.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @param int $dtrId The ID of the DTR record to update.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with a success message or an error message.
     */
    public function timeOut(Request $request, $dtrId)
    {
        // Validate the request data
        $request->validate([
            'end_of_the_day_report' => 'required|string|max:500',
            'end_of_the_day_images' => 'required|array|max:4',
            'end_of_the_day_images.*' => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048'
        ]);

        try {
            // Get the authenticated user's ID and object
            $userId = Auth::id();
            $user = Auth::user();

            // Find the DTR record
            $dtr = Dtr::where('user_id', $userId)
                ->where('id', $dtrId)
                ->firstOrFail();

            // Check if the existing time_in has a time_out
            if ($dtr->time_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to time-out. Record has already been timed out.'
                ], 400);
            }

            // Check if there is any open break (break_time set but resume_time is null)
            $openBreak = $dtr->breaks()->whereNull('resume_time')->exists();
            if ($openBreak) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have an open break that needs to be resumed before timing out.'
                ], 400);
            }

            // Extract full/part-time role and shift role
            $timeOut = $this->workHoursService->evaluateTimeOut($user, now());
            $timeIn = Carbon::parse($dtr->time_in);

            // Evaluate if the hour difference of time in and time out are appropriate for the role
            if (!$this->workHoursService->findTimeInTimeOutDifference($user, $dtr, $timeIn, $timeOut)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient worked hours. You need to work at least 8 hours before timing out for full-time or 4 hours for part-time.'
                ], 400);
            }

            // Update the time_out field and end_of_the_day_report field of the DTR record
            $dtr->time_out = $timeOut;
            $dtr->end_of_the_day_report = $request->end_of_the_day_report;
            $dtr->save();

            // Handle image uploads
            if ($request->hasFile('end_of_the_day_images')) {
                $images = $request->file('end_of_the_day_images');
                $uploadedImages = [];

                foreach ($images as $image) {
                    $path = $image->store('end_of_the_day_images', 'public');
                    $uploadedImages[] = new EndOfTheDayReportImage([
                        'end_of_the_day_image' => $path
                    ]);
                }

                if (count($uploadedImages) > 4) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only upload up to 4 images.'
                    ], 400);
                }

                $dtr->endOfTheDayReportImages()->saveMany($uploadedImages);
            }

            return response()->json([
                'success' => true,
                'message' => 'Time out recorded successfully.'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle DTR record not found
            return response()->json([
                'success' => false,
                'message' => 'DTR record not found.',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while recording the time out.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
