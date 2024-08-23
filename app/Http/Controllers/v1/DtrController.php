<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\DtrController\StoreTimeInRequest;
use App\Http\Requests\v1\DtrController\StoreTimeOutRequest;
use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\EndOfTheDayReportImage;
use App\Services\v1\EvaluateScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DtrController extends Controller
{
    protected $user;
    protected $evaluateScheduleService;

    public function __construct(EvaluateScheduleService $evaluateScheduleService)
    {
        $this->user = Auth::user();
        $this->evaluateScheduleService = $evaluateScheduleService;
    }

    public function index(Request $request): JsonResponse
    {
        // Retrieve the query parameters from the request
        $sort = $request->input('sort', 'dtr_id'); 
        $order = $request->input('order', 'desc'); 
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);
    
        // Build the query
        $query = Dtr::select('dtr_id', 'dtr_time_in', 'dtr_time_out', 'dtr_end_of_the_day_report', 'dtr_is_overtime')
                    ->where('user_id', $this->user->user_id)
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

    public function show(int $dtrId): JsonResponse
    {
        // Retrieve the DTR record for the given ID and check if it exists
        $dtr = Dtr::select('dtr_id', 'dtr_time_in', 'dtr_time_out', 'dtr_end_of_the_day_report', 'dtr_is_overtime')
                ->where('user_id', $this->user->user_id)
                ->where('dtr_id', $dtrId)
                ->whereNull(['dtr_absence_date', 'dtr_absence_reason'])
                ->first();

        // Handle DTR record not found
        if (!$dtr) {
            return response()->json(['message' => 'DTR record not found.'], 404);
        }

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'DTR record retrieved successfully.',
            'data' => $dtr,
        ], 200);
    }

    public function storeTimeIn(StoreTimeInRequest $request): JsonResponse
    {
        // Initialize file path for potential rollback
        $dtrTimeInFilePath = null;

        try {
            // Evaluate the schedule start time
            $isWithinSchedule = $this->evaluateScheduleService->evaluateSchedule($this->user);

            // Handle schedule start time failure
            if (! $isWithinSchedule) {
                return response()->json(['message' => 'Outside of schedule.'], 409);
            }

            // Check if there is an open time in session
            $openTimeIn = Dtr::where('user_id', $this->user->user_id)
                            ->whereNull(['dtr_time_out', 'dtr_time_out_image', 
                                        'dtr_absence_date', 'dtr_absence_reason', 
                            ])            
                            ->exists();
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

            // Handle end of the day report images
            $endOfTheDayReportImages = $request->file('end_of_the_day_report_images');
            foreach ($endOfTheDayReportImages as $file) {
                $path = $file->store('end_of_the_day_report_images');
                $endOfTheDayReportImagePaths[] = $path;
            }

            // Retrieve the most recent DTR record with a time-in and eager load breaks
            $dtrTimeIn = Dtr::with(['breaks' => function ($query) {
                            $query->whereNull('dtr_break_resume_time');
                        }])
                        ->where('user_id', $this->user->user_id)
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

            // Delete end of the day report images
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
            $dtrWithBreak = Dtr::with(['breaks' => function ($query) {
                                    $query->whereNull('dtr_break_resume_time');
                                }])
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

    public function storeResume(): JsonResponse
    {
        try {
            // Retrieve the DTR record with a time-in and no time-out
            $dtrWithBreak = Dtr::with(['breaks' => function ($query) {
                                    $query->whereNull('dtr_break_resume_time');
                                }])
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
