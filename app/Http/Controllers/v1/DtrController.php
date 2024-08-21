<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\DtrController\StoreTimeInRequest;
use App\Http\Requests\v1\DtrController\StoreTimeOutRequest;
use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\EndOfTheDayReportImage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DtrController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function storeTimeIn(StoreTimeInRequest $request): JsonResponse
    {
        // Initialize file path for potential rollback
        $dtrTimeInFilePath = null;

        try {
            // Check if there is an open time in session
            $openTimeIn = Dtr::where('user_id', $this->user->user_id)
                            ->whereNull(['dtr_time_out', 'dtr_time_out_image', 
                                        'dtr_absence_date', 'dtr_absence_reason', 
                            ])            
                            ->first();
            if ($openTimeIn) {
                return response()->json(['message' => 'Time in failed. You currently have an open time in session.'], 400);
            }

            // Handle time in image file upload
            $dtrTimeInFilePath = $request->file('dtr_time_in_image')->store('dtr_time_in_images');

            // Create a new DTR record with the time in and the uploaded image path
            Dtr::create([
                'user_id' => $this->user->user_id,
                'dtr_time_in' => Carbon::now(),
                'dtr_time_in_image' => $dtrTimeInFilePath,
            ]);

            return response()->json(['message' => 'Timed in successfully.'], 201);
        } catch (\Exception $e) {
            // Delete the uploaded file if it exists
            if ($dtrTimeInFilePath) {
                Storage::delete($dtrTimeInFilePath);
            }

            // Return an error response
            return response()->json(['message' => 'Failed to time in.'], 500);
        }
    }

    public function storeTimeOut(StoreTimeOutRequest $request): JsonResponse
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
                return response()->json(['message' => 'Time out file upload failed.'], 400);
            }

            // Handle end of the day report images
            $endOfTheDayReportImages = $request->file('end_of_the_day_report_images');
            foreach ($endOfTheDayReportImages as $file) {
                $path = $file->store('end_of_the_day_report_images');
                if (!$path) {
                    return response()->json(['message' => 'End of the day report file upload failed.'], 400);
                }
                $endOfTheDayReportImagePaths[] = $path;
            }

            // Retrieve the most recent DTR record with a time-in
            $dtrTimeIn = Dtr::where('user_id', $this->user->user_id)
                            ->whereNotNull(['dtr_time_in', 'dtr_time_in_image'])
                            ->whereNull(['dtr_time_out', 'dtr_time_out_image', 
                                        'dtr_absence_date', 'dtr_absence_reason', 
                            ])   
                            ->first();

            if (!$dtrTimeIn) {
                return response()->json(['message' => 'Failed to time out. You have not timed in yet.'], 400);
            }

            // Check for any open breaks
            $openBreak = DtrBreak::where('dtr_id', $dtrTimeIn->dtr_id)
                        ->whereNull('dtr_break_resume_time')
                        ->first();
            if ($openBreak) {
                return response()->json(['message' => 'Failed to time out. You have an open break session.'], 400);
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

            return response()->json(['message' => 'Failed to time out.'], 500);
        }
    }

    public function storeBreak(): JsonResponse
    {
        try {
            // Retrieve the DTR record with a time-in and no time-out
            $dtrWithBreak = Dtr::with('breaks')
                                ->where('user_id', $this->user->user_id)
                                ->whereNull(['dtr_time_out', 'dtr_time_out_image', 
                                            'dtr_absence_date', 'dtr_absence_reason', 
                                ])                                   
                                ->first();
        
            // Check if there is an open time-in session
            if (!$dtrWithBreak) {
                return response()->json(['message' => 'Failed to add break time. You have not timed in yet.'], 400);
            }
    
            // Check if there is an open break session
            $openBreak = $dtrWithBreak->breaks()->whereNull('dtr_break_resume_time')->first();
            if ($openBreak) {
                return response()->json(['message' => 'Failed to add break time. You have an open break time session.'], 400);
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
        }
    }    

    public function storeResume(): JsonResponse
    {
        try {
            // Retrieve the DTR record with a time-in and no time-out
            $dtrWithBreak = Dtr::with('breaks')
                                ->where('user_id', $this->user->user_id)
                                ->whereNull(['dtr_time_out', 'dtr_time_out_image', 
                                            'dtr_absence_date', 'dtr_absence_reason', 
                                ])   
                                ->first();
    
            // Check if there is an open time-in session
            if (!$dtrWithBreak) {
                return response()->json(['message' => 'Failed to add resume time. You have not timed in yet.'], 400);
            }
    
            // Check if there is an open break session
            $openBreak = $dtrWithBreak->breaks()->whereNull('dtr_break_resume_time')->first();
            if (!$openBreak) {
                return response()->json(['message' => 'Failed to add resume time. There is no open break session.'], 400);
            }
    
            // Record the resume time in the database
            $openBreak->dtr_break_resume_time = Carbon::now();
            $openBreak->save();
    
            return response()->json(['message' => 'Resume time was added successfully.'], 201);
        } catch (\Exception $e) {
            // Return an error response
            return response()->json(['message' => 'Failed to add resume time.'], 500);
        }
    }    
}
