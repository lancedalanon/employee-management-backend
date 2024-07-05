<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dtr;
use App\Models\DtrBreak;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DtrController extends Controller
{
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
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with a success message or an error message.
     */
    public function timeIn()
    {
        try {
            // Get the authenticated user's ID
            $userId = Auth::id();
            $currentDate = Carbon::now()->toDateString();

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

            // Check if there is already a time_in entry for the current date
            $existingTimeInToday = Dtr::where('user_id', $userId)
                ->whereDate('time_in', $currentDate)
                ->exists();

            if ($existingTimeInToday) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already timed in today.'
                ], 400);
            }

            // Create a new DTR entry for the authenticated user
            $dtr = new Dtr();
            $dtr->user_id = $userId;
            $dtr->time_in = Carbon::now();
            $dtr->save();

            return response()->json([
                'success' => true,
                'message' => 'Time in recorded successfully.'
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
            // Find the DTR record
            $dtr = Dtr::findOrFail($dtrId);

            // Convert the time_in to a Carbon instance
            $timeInDate = Carbon::parse($dtr->time_in)->toDateString();

            // Check if the date of time_in is the same as the current date
            if ($timeInDate !== Carbon::now()->toDateString()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only start a break on the same date as your time in.'
                ], 400);
            }

            // Check if there is an unfilled resume_time for the same DTR record
            $unfilledResume = $dtr->breaks()->whereNull('resume_time')->exists();
            if ($unfilledResume) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have an open break that needs to be resumed before starting a new one.'
                ], 400);
            }

            // Record the break time
            $dtrBreak = new DtrBreak();
            $dtrBreak->dtr_id = $dtr->id;
            $dtrBreak->break_time = Carbon::now();
            $dtrBreak->save();

            return response()->json([
                'success' => true,
                'message' => 'Break started successfully.'
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
            // Find the DTR record
            $dtr = Dtr::findOrFail($dtrId);

            // Convert the time_in to a Carbon instance
            $timeInDate = Carbon::parse($dtr->time_in)->toDateString();

            // Check if the date of time_in is the same as the current date
            if ($timeInDate !== Carbon::now()->toDateString()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only resume a break on the same date as your time in.'
                ], 400);
            }

            // Find the last break record (assuming you're resuming the latest break)
            $latestBreak = $dtr->breaks()
                ->whereDate('break_time', Carbon::now()->toDateString())
                ->latest()
                ->first();

            // Check if there is a break to resume and that it is not already resumed
            if (is_null($latestBreak) || !is_null($latestBreak->resume_time)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No break to resume or the break is already resumed.'
                ], 400);
            }

            // Update the resume time for the latest break
            $latestBreak->resume_time = Carbon::now();
            $latestBreak->save();

            return response()->json([
                'success' => true,
                'message' => 'Break resumed successfully.'
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
     * @param int $dtrId The ID of the DTR record to update.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response with a success message or an error message.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the DTR record is not found.
     */
    public function timeOut($dtrId)
    {
        try {
            // Find the DTR record
            $dtr = Dtr::findOrFail($dtrId);

            // Convert the time_in to a Carbon instance
            $timeInDate = Carbon::parse($dtr->time_in)->toDateString();

            // Check if the date of time_in is the same as the current date
            if ($timeInDate !== Carbon::now()->toDateString()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only time out on the same date as your time in.'
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

            // Calculate the total working hours including break-resume sessions
            $timeIn = Carbon::parse($dtr->time_in);
            $currentTime = Carbon::now();
            $totalWorkDuration = $timeIn->diffInSeconds($currentTime);

            // Subtract the duration of all breaks
            $breaks = $dtr->breaks()->get();
            foreach ($breaks as $break) {
                if ($break->resume_time) {
                    $breakStart = Carbon::parse($break->break_time);
                    $breakEnd = Carbon::parse($break->resume_time);
                    $totalWorkDuration -= $breakStart->diffInSeconds($breakEnd);
                }
            }

            // Convert total work duration to hours
            $totalWorkHours = $totalWorkDuration / 3600;

            // Check if total work hours is at least 8 hours
            if ($totalWorkHours < 8) {
                return response()->json([
                    'success' => false,
                    'message' => 'You need to work at least 8 hours before timing out.'
                ], 400);
            }

            // Update the time_out field of the DTR record
            $dtr->time_out = Carbon::now();
            $dtr->save();

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
