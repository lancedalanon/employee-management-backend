<?php

namespace App\Services\v1;

use App\Models\Company;
use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\EndOfTheDayReportImage;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DtrService
{
    protected EvaluateScheduleService $evaluateScheduleService;

    public function __construct(EvaluateScheduleService $evaluateScheduleService)
    {
        $this->evaluateScheduleService = $evaluateScheduleService;
    }

    public function getDtrs(Authenticatable $user, array $validatedData): JsonResponse
    {
        // Retrieve the query parameters from the request
        $sort = $validatedData['sort']; 
        $order = $validatedData['order']; 
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];

        // Build the query
        $query = Dtr::select('dtr_id', 'dtr_time_in', 'dtr_time_out', 'dtr_end_of_the_day_report', 'dtr_is_overtime')
                    ->where('user_id', $user->user_id)
                    ->whereNull(['dtr_absence_date', 'dtr_absence_reason']);

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('dtr_end_of_the_day_report', 'LIKE', "%$search%");
            });
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        // Paginate the results
        $dtrs = $query->paginate($perPage, ['*'], 'page', $page);

        // Construct the response data
        $responseData = [
            'message' => $dtrs->isEmpty() ? 'No DTR records found for the provided criteria.' : 'DTR records retrieved successfully.',
            'data' => $dtrs,
        ];

        // Return the response as JSON with a 200 status code
        return response()->json($responseData, 200);
    }

    public function getDtrById(Authenticatable $user, int $dtrId): JsonResponse
    {
        // Retrieve the DTR record for the given ID and check if it exists
        $dtr = Dtr::select('dtr_id', 'dtr_time_in', 'dtr_time_out', 
                            'dtr_end_of_the_day_report', 'dtr_is_overtime',
                            'dtr_time_in_image', 'dtr_time_out_image', 'dtr_reason_of_late_entry',
                            'dtr_end_of_the_day_report', 'dtr_is_overtime')
                ->where('user_id', $user->user_id)
                ->where('dtr_id', $dtrId)
                ->whereNull(['dtr_absence_date', 'dtr_absence_reason'])
                ->first();

        // Handle DTR record not found
        if (!$dtr) {
            return response()->json(['message' => 'DTR record not found.'], 404);
        }

        // Generate URLs for images if they exist
        if ($dtr->dtr_time_in_image) {
            $responseData['dtr_time_in_image_url'] = Storage::url($dtr->dtr_time_in_image);
        }

        if ($dtr->dtr_time_out_image) {
            $responseData['dtr_time_out_image_url'] = Storage::url($dtr->dtr_time_out_image);
        }

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'DTR record retrieved successfully.',
            'data' => $dtr,
        ], 200);
    }

    public function createTimeIn(Authenticatable $user, array $validatedData): JsonResponse
    {
        // Initialize file path for potential rollback
        $dtrTimeInFilePath = null;

        // Handle validated image file of dtr_time_in_image
        $imageFile = $validatedData['dtr_time_in_image'];

        try {
            // Evaluate the schedule start time
            $isWithinSchedule = $this->evaluateScheduleService->evaluateSchedule($user);

            // Handle schedule start time failure
            if (! $isWithinSchedule) {
                return response()->json(['message' => 'Outside of schedule.'], 409);
            }

            // Check if there is an open time in session
            $openTimeIn = Dtr::where('user_id', $user->user_id)
                            ->whereNull(['dtr_time_out', 'dtr_time_out_image', 
                                        'dtr_absence_date', 'dtr_absence_reason', 
                            ])            
                            ->exists();
                            
            // Handle open time in session failure
            if ($openTimeIn) {
                return response()->json(['message' => 'Time in failed. You currently have an open time in session.'], 400);
            }

            // Create unique file name for the image file
            $fileName = 'dtr_time_in_images/' . uniqid() . '.' . $imageFile->getClientOriginalExtension();

            // Store the image file using Storage::put (works across different storage disks like S3, local)
            $dtrTimeInFilePath = Storage::put($fileName, file_get_contents($imageFile));

            // Generate the URL to the stored image file
            $imageUrl = Storage::url($fileName);

            // Create a new DTR record with the time in and the uploaded image path
            Dtr::create([
                'user_id' => $user->user_id,
                'dtr_time_in' => Carbon::now(),
                'dtr_time_in_image' => $imageUrl,
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

    public function createTimeOut(Authenticatable $user, array $validatedData): JsonResponse
    {
        // Start a database transaction
        DB::beginTransaction();
    
        // Initialize file paths for potential rollback
        $endOfTheDayReportImagePaths = [];
        $dtrTimeOutFilePath = null;
    
        // Handle validated file uploads
        $dtrTimeOutImage = $validatedData['dtr_time_out_image'];
        $endOfTheDayReportImages = $validatedData['end_of_the_day_report_images'];
    
        // Handle end-of-the-day report
        $dtrEndOfTheDayReport = $validatedData['dtr_end_of_the_day_report'];

        try {
            // Retrieve the most recent DTR record with a time-in and eager load breaks
            $dtrTimeIn = Dtr::with(['breaks' => function ($query) {
                                $query->whereNull('dtr_break_resume_time');
                            }])
                            ->where('user_id', $user->user_id)
                            ->whereNotNull(['dtr_time_in', 'dtr_time_in_image'])
                            ->whereNull([
                                'dtr_time_out', 'dtr_time_out_image',
                                'dtr_absence_date', 'dtr_absence_reason',
                            ])
                            ->first();
    
            // Handle DTR record not found
            if (!$dtrTimeIn) {
                return response()->json(['message' => 'Failed to time out. You have not timed in yet.'], 400);
            }
    
            // Check for any open breaks
            if ($dtrTimeIn->breaks->isNotEmpty()) {
                return response()->json(['message' => 'Failed to time out. You have an open break session.'], 400);
            }
            
            // Convert `dtr_time_in` to a Carbon instance
            $dtrTimeInTime = Carbon::parse($dtrTimeIn->dtr_time_in);

            // Check if time-out is within the allowed late entry time
            if ($this->evaluateScheduleService->isTimeOutLate($user, $dtrTimeInTime)) {
                return response()->json(['message' => 'Failed to time out. The time-out is too late. Please use the late entry clearance option.'], 409);
            }

            // Create a unique file name for the time-out image
            $dtrTimeOutFileName = 'dtr_time_out_images/' . uniqid() . '.' . $dtrTimeOutImage->getClientOriginalExtension();

            // Store the image file using Storage::put (works across different storage disks like S3, local)
            $dtrTimeOutFilePath = Storage::put($dtrTimeOutFileName, file_get_contents($dtrTimeOutImage));

            // Generate the URL to the stored image file
            $dtrTimeOutFileNameUrl = Storage::url($dtrTimeOutFileName);

            // Handle end-of-the-day report images
            foreach ($endOfTheDayReportImages as $file) {
                $endOfTheDayReportFileName = 'end_of_the_day_report_images/' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = Storage::put($endOfTheDayReportFileName, file_get_contents($file));
                $endOfTheDayReportImagesUrl = Storage::url($endOfTheDayReportFileName);
                $endOfTheDayReportImagePaths[] = $endOfTheDayReportImagesUrl;
            }
    
            // Update the DTR record with time-out details
            $dtrTimeIn->update([
                'dtr_time_out' => Carbon::now(),
                'dtr_time_out_image' => $dtrTimeOutFileNameUrl,
                'dtr_end_of_the_day_report' => $dtrEndOfTheDayReport,
            ]);
    
            // Store end-of-the-day report images
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
    
            // Delete end-of-the-day report images
            foreach ($endOfTheDayReportImagePaths as $path) {
                Storage::delete($path);
            }
    
            return response()->json(['message' => 'Failed to time out.'], 500);
        }
    }    

    public function createBreak(Authenticatable $user): JsonResponse 
    {
        try {
            // Retrieve the DTR record with a time-in and no time-out
            $dtrWithBreak = Dtr::with(['breaks' => function ($query) {
                                    $query->whereNull('dtr_break_resume_time');
                                }])
                                ->where('user_id', $user->user_id)
                                ->whereNull(['dtr_time_out', 'dtr_time_out_image', 
                                            'dtr_absence_date', 'dtr_absence_reason', 
                                ])                                   
                                ->first();
        
            // Check if there is an open time-in session
            if (!$dtrWithBreak) {
                return response()->json(['message' => 'Failed to add break time. You have not timed in yet.'], 400);
            }
    
            // Check if there is an open break session
            if ($dtrWithBreak->breaks->isNotEmpty()) {
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

    public function createResume(Authenticatable $user): JsonResponse
    {
        try {
            // Retrieve the DTR record with a time-in and no time-out
            $dtrWithBreak = Dtr::with(['breaks' => function ($query) {
                                    $query->whereNull('dtr_break_resume_time');
                                }])
                                ->where('user_id', $user->user_id)
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

    public function updateTimeOut(Authenticatable $user, array $validatedData): JsonResponse 
    {
        // Start a database transaction
        DB::beginTransaction();

        // Initialize file paths for potential rollback
        $endOfTheDayReportImagePaths = [];
        $dtrTimeOutFilePath = null;

        // Handle validated file uploads
        $dtrTimeOutImage = $validatedData['dtr_time_out_image'];
        $endOfTheDayReportImages = $validatedData['end_of_the_day_report_images'];

        // Handle end of the day report
        $dtrEndOfTheDayReport = $validatedData['dtr_end_of_the_day_report'];
        $dtrReasonOfLateEntry = $validatedData['dtr_reason_of_late_entry'];

        try {
            // Retrieve the most recent DTR record with a time-in and eager load breaks
            $dtrTimeIn = Dtr::with(['breaks' => function ($query) {
                            $query->whereNull('dtr_break_resume_time');
                        }])
                        ->where('user_id', $user->user_id)
                        ->whereNotNull(['dtr_time_in', 'dtr_time_in_image'])
                        ->whereNull([
                            'dtr_time_out', 'dtr_time_out_image',
                            'dtr_absence_date', 'dtr_absence_reason',
                        ])
                        ->first();

            // Handle DTR record not found
            if (!$dtrTimeIn) {
                return response()->json(['message' => 'Failed to time out. You have not timed in yet.'], 400);
            }

            // Check for any open breaks
            if ($dtrTimeIn->breaks->isNotEmpty()) {
                return response()->json(['message' => 'Failed to time out. You have an open break session.'], 400);
            }

            // Convert `dtr_time_in` to a Carbon instance
            $dtrTimeInTime = Carbon::parse($dtrTimeIn->dtr_time_in);

            // Check if time-out is not the allowed late entry time
            if (!($this->evaluateScheduleService->isTimeOutLate($user, $dtrTimeInTime))) {
                return response()->json(['message' => 'Time-out is not a late entry.'], 409);
            }

            // Create a unique file name for the time-out image
            $dtrTimeOutFileName = 'dtr_time_out_images/' . uniqid() . '.' . $dtrTimeOutImage->getClientOriginalExtension();

            // Store the image file using Storage::put (works across different storage disks like S3, local)
            $dtrTimeOutFilePath = Storage::put($dtrTimeOutFileName, file_get_contents($dtrTimeOutImage));

            // Generate the URL to the stored image file
            $dtrTimeOutFileNameUrl = Storage::url($dtrTimeOutFileName);

            // Handle end-of-the-day report images
            foreach ($endOfTheDayReportImages as $file) {
                $endOfTheDayReportFileName = 'end_of_the_day_report_images/' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = Storage::put($endOfTheDayReportFileName, file_get_contents($file));
                $endOfTheDayReportImagesUrl = Storage::url($endOfTheDayReportFileName);
                $endOfTheDayReportImagePaths[] = $endOfTheDayReportImagesUrl;
            }
    
            // Update the DTR record with time-out details
            $dtrTimeIn->update([
                'dtr_time_out' => Carbon::now(),
                'dtr_time_out_image' => $dtrTimeOutFileNameUrl,
                'dtr_end_of_the_day_report' => $dtrEndOfTheDayReport,
                'dtr_reason_of_late_entry' => $dtrReasonOfLateEntry,
            ]);
    
            // Store end-of-the-day report images
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

            // Delete end of the day report images
            foreach ($endOfTheDayReportImagePaths as $path) {
                Storage::delete($path);
            }

            return response()->json(['message' => 'Failed to time out.'], 500);
        }
    }
}