<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\EndOfTheDayReportImage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DtrController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function storeTimeIn(Request $request): JsonResponse
    {
        // Start a database transaction
        DB::beginTransaction();

        $dtrTimeInFilePath = null;

        try {
            // Check if there is an open time in session
            $openTimeIn = Dtr::where('user_id', $this->user->user_id)
                            ->whereNull('dtr_time_out')     
                            ->whereNull('dtr_time_out_image')     
                            ->first();
            if ($openTimeIn) {
                throw new \Exception('Failed to time in. You currently have an open time in session.');
            }

            // Handle time in image file upload
            $dtrTimeInFilePath = $request->file('dtr_time_in_image')->store('dtr_time_in_images');
            if (!$dtrTimeInFilePath) {
                throw new \Exception('File upload failed.');
            }

            // Create a new DTR record with the time in and the uploaded image path
            Dtr::create([
                'user_id' => $this->user->user_id,
                'dtr_time_in' => Carbon::now(),
                'dtr_time_in_image' => $dtrTimeInFilePath,
            ]);

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Timed in successfully.'], 201);
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            // Delete the uploaded file if it exists
            if ($dtrTimeInFilePath) {
                Storage::delete($dtrTimeInFilePath);
            }

            // Return an error response
            return response()->json(['message' => 'Failed to time in.'], 500);
        } 
    }

    public function storeTimeOut(Request $request): JsonResponse
    {
        // Start a database transaction
        DB::beginTransaction();

        // Initialize file paths for potential rollback
        $endOfTheDayReportImagePaths = [];
        $dtrTimeOutFilePath = null;

        try {
            // Handle DTR time out image file upload
            $dtrTimeOutFilePath = $request->file('dtr_time_out_image')->store('dtr_time_out_images');
            if (!$dtrTimeOutFilePath) {
                throw new \Exception('Time out file upload failed.');
            }
            
            // Handle end of the day report images
            $endOfTheDayReportImages = $request->file('end_of_the_day_report_images');
            foreach ($endOfTheDayReportImages as $file) {
                $path = $file->store('end_of_the_day_report_images');
                if (!$path) {
                    throw new \Exception('End of the day report file upload failed.');
                }
                $endOfTheDayReportImagePaths[] = $path;
            }

            // Retrieve the most recent DTR record with a time-in
            $dtrTimeIn = Dtr::where('user_id', $this->user->user_id)
                            ->whereNotNull(['dtr_time_in', 'dtr_time_in_image'])
                            ->whereNull(['dtr_time_out', 'dtr_time_out_image'])
                            ->first();

            if (!$dtrTimeIn) {
                throw new \Exception('Failed to time out. You have not timed in yet.');
            }

            // Check for any open breaks
            $openBreak = DtrBreak::where('dtr_id', $dtrTimeIn->dtr_id)
                        ->whereNull('dtr_break_resume_time')
                        ->first();
            if ($openBreak) {
                throw new \Exception('Failed to time out. You have an open break session.');
            }

            // Update the DTR record with time out details
            $dtrTimeIn->update([
                'dtr_time_out' => Carbon::now(),
                'dtr_time_out_image' => $dtrTimeOutFilePath,
                'dtr_end_of_the_day_report' => $request->input('dtr_end_of_the_day_report'),
            ]);

            // Store end of the day report images
            foreach ($endOfTheDayReportImagePaths as $path) {
                EndOfTheDayReportImage::create([
                    'dtr_id' => $dtrTimeIn->dtr_id,
                    'end_of_the_day_report_image' => $path,
                ]);
            }

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Timed out successfully.'], 201);
        } catch (\Exception $e) {
            // Rollback transaction and delete files on failure
            DB::rollBack();

            // Delete already uploaded files
            if ($dtrTimeOutFilePath) {
                Storage::delete($dtrTimeOutFilePath);
            }

            foreach ($endOfTheDayReportImagePaths as $path) {
                Storage::delete($path);
            }

            Log::error($e->getMessage());

            return response()->json(['message' => 'Failed to time out.'], 500);
        }
    }

    public function storeBreak(): JsonResponse
    {
        try {
            // Retrieve the DTR record with a time-in and a break session
            $dtrWithBreak = Dtr::with('breaks')
                            ->where('user_id', $this->user->user_id)
                            ->whereNull('dtr_time_out')     
                            ->whereNull('dtr_time_out_image')  
                            ->first();

            // Check if there is an open time in session
            if (!$dtrWithBreak) {
                throw new \Exception('Failed to add break time. You have not timed in yet.');
            }

            // Check if there is an open break session
            $openBreak = $dtrWithBreak->breaks()->whereNull('dtr_break_resume_time')->first();
            if ($openBreak) {
                throw new \Exception('Failed to add break time. You have an open break time session.');
            }

            // Record the break time in the database
            DtrBreak::create([
                'dtr_id' => $dtrWithBreak->dtr_id,
                'dtr_break_break_time' => Carbon::now(),
            ]);

            return response()->json(['message' => 'Break time was added successfully.'], 201);
        } catch (\Exception $e) {
            // Return an error response
            return response()->json(['message' => 'Failed to add break time.'], 500);
        };
    }

    public function storeResume(): JsonResponse
    {
        try {
            // Retrieve the DTR record with a time-in and a break session
            $dtrWithBreak = Dtr::with('breaks')
                            ->where('user_id', $this->user->user_id)
                            ->whereNull('dtr_time_out')     
                            ->whereNull('dtr_time_out_image')  
                            ->first();

            // Check if there is an open time in session
            if (!$dtrWithBreak) {
                throw new \Exception('Failed to add resume time. You have not timed in yet.');
            }

            // Check if there is an open break session
            $openBreak = $dtrWithBreak->breaks()->whereNull('dtr_break_resume_time')->first();
            if (!$openBreak) {
                throw new \Exception('Failed to add resume time. You have an open break time session.');
            }

            $openBreak->dtr_break_resume_time = Carbon::now();
            $openBreak->save();

            return response()->json(['message' => 'Resume time was added successfully.'], 201);
        } catch (\Exception $e) {
            // Return an error response
            return response()->json(['message' => 'Failed to add resume time.'], 500);
        }
    }
}
