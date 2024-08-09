<?php

namespace App\Http\Controllers;

use App\Models\Dtr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class LeaveRequestController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function index(Request $request) 
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            // Retrieve paginated leave requests for the authenticated user
            $leaveRequests = Dtr::where('user_id', $this->user->user_id)
                    ->whereNotNull('absence_date')
                    ->whereNotNull('absence_reason')
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage, ['*'], 'page', $page);
                    
            // Return the success response with leave requests data
            return Response::json([
                'message' => 'Leave requests retrieved successfully.',
                'current_page' => $leaveRequests->currentPage(),
                'data' => $leaveRequests->items(),
                'first_page_url' => $leaveRequests->url(1),
                'from' => $leaveRequests->firstItem(),
                'last_page' => $leaveRequests->lastPage(),
                'last_page_url' => $leaveRequests->url($leaveRequests->lastPage()),
                'links' => $leaveRequests->linkCollection()->toArray(),
                'next_page_url' => $leaveRequests->nextPageUrl(),
                'path' => $leaveRequests->path(),
                'per_page' => $leaveRequests->perPage(),
                'prev_page_url' => $leaveRequests->previousPageUrl(),
                'to' => $leaveRequests->lastItem(),
                'total' => $leaveRequests->total(),
            ], 200);
        } catch (\Exception $e) {
            // Return a generic error response
            return Response::json([
                'message' => 'An error occurred while retrieving the leave requests.',
            ], 500);
        }
    }

    public function show($leaveRequestId) 
    {
        try {
            // Retrieve the leave request for the authenticated user
            $leaveRequest = Dtr::where('dtr_id', $leaveRequestId)
                    ->where('user_id', $this->user->user_id)
                    ->whereNotNull('absence_date')
                    ->whereNotNull('absence_reason')
                    ->first();

            // Check if the leave request was found
            if (!$leaveRequest) {
                return Response::json([
                    'message' => 'Leave request not found.'
                ], 404);
            }

            // Return the success response with the leave request data
            return Response::json([
                'message' => 'Leave request retrieved successfully.',
                'data' => $leaveRequest
            ], 200);
        } catch (\Exception $e) {
            // Return a generic error response
            return Response::json([
                'message' => 'An error occurred while retrieving the leave request.',
            ], 500);
        }
    }

    public function indexAdmin(int $perPage, int $page) 
    {
        try {
            // Retrieve paginated leave requests for the authenticated user
            $leaveRequests = Dtr::whereNotNull('absence_date')
                    ->whereNotNull('absence_reason')
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage, ['*'], 'page', $page);
                    
            // Return the success response with leave requests data
            return Response::json([
                'message' => 'Leave requests retrieved successfully.',
                'current_page' => $leaveRequests->currentPage(),
                'data' => $leaveRequests->items(),
                'first_page_url' => $leaveRequests->url(1),
                'from' => $leaveRequests->firstItem(),
                'last_page' => $leaveRequests->lastPage(),
                'last_page_url' => $leaveRequests->url($leaveRequests->lastPage()),
                'links' => $leaveRequests->linkCollection()->toArray(),
                'next_page_url' => $leaveRequests->nextPageUrl(),
                'path' => $leaveRequests->path(),
                'per_page' => $leaveRequests->perPage(),
                'prev_page_url' => $leaveRequests->previousPageUrl(),
                'to' => $leaveRequests->lastItem(),
                'total' => $leaveRequests->total(),
            ], 200);
        } catch (\Exception $e) {
            // Return a generic error response
            return Response::json([
                'message' => 'An error occurred while retrieving the leave requests.',
            ], 500);
        }
    }

    public function showAdmin($leaveRequestId) 
    {
        try {
            // Retrieve the leave request for the authenticated user
            $leaveRequest = Dtr::where('dtr_id', $leaveRequestId)
                    ->whereNotNull('absence_date')
                    ->whereNotNull('absence_reason')
                    ->first();

            // Check if the leave request was found
            if (!$leaveRequest) {
                return Response::json([
                    'message' => 'Leave request not found.'
                ], 404);
            }

            // Return the success response with the leave request data
            return Response::json([
                'message' => 'Leave request retrieved successfully.',
                'data' => $leaveRequest
            ], 200);
        } catch (\Exception $e) {
            // Return a generic error response
            return Response::json([
                'message' => 'An error occurred while retrieving the leave request.',
            ], 500);
        }
    }

    public function bulkStore(Request $request)
    {
        // Define validation rules
        $rules = [
            'absence_start_date' => 'required|date|after:today',
            'absence_end_date' => 'required|date|after_or_equal:absence_start_date|before:9999-01-01',
            'absence_reason' => 'required|string|max:255',
        ];

        // Validate the request
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
            ], 422);
        }

        // Initialize an empty array for bulk insertion
        $data = [];

        // Create a date range and populate the data array
        $startDate = Carbon::parse($request->input('absence_start_date'));
        $endDate = Carbon::parse($request->input('absence_end_date'));
        $absenceReason = $request->input('absence_reason');

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $data[] = [
                'user_id' => $this->user->user_id,
                'absence_date' => $date->format('Y-m-d'),
                'absence_reason' => $absenceReason,
            ];
        }

        // Chunked insertion to minimize memory usage and avoid timeouts
        $chunkSize = 500; // Adjust based on your database capabilities
        DB::transaction(function () use ($data, $chunkSize) {
            foreach (array_chunk($data, $chunkSize) as $chunk) {
                DB::table('dtrs')->insert($chunk);
            }
        });

        // Return a success response
        return Response::json([
            'message' => 'DTRs stored successfully',
        ], 201);
    }

    public function update(int $leaveRequestId)
    {
        $leaveRequest = Dtr::where('dtr_id', $leaveRequestId)
            ->whereNotNull('absence_date')
            ->whereNotNull('absence_reason')
            ->whereNull('absence_approved_at')
            ->first();

        if (!$leaveRequest) {
            return Response::json([
                'message' => 'Failed to retrieve leave request.',
            ], 404);
        }

        $leaveRequest->absence_approved_at = Carbon::now();
        $leaveRequest->save();

        return Response::json([
            'message' => 'Leave request was successfully approved.',
        ], 200);
    }

    public function bulkUpdate(Request $request)
    {
        // Define validation rules
        $rules = [
            'dtr_ids' => 'required|array',
            'dtr_ids.*' => 'required|exists:dtrs,dtr_id',
        ];
    
        // Validate the request
        $validator = Validator::make($request->all(), $rules);
    
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
            ], 422);
        }
    
        // Collect the DTR IDs to update
        $dtrIds = $request->input('dtr_ids');
    
        // Perform a bulk update within a transaction
        DB::transaction(function () use ($dtrIds) {
            DB::table('dtrs')
                ->whereIn('dtr_id', $dtrIds)
                ->update(['absence_approved_at' => Carbon::now()]);
        });
    
        // Return a success response
        return Response::json([
            'message' => 'DTRs updated successfully',
        ], 200);
    }       

    public function destroy(int $leaveRequestId)
    {
        $leaveRequest = Dtr::where('dtr_id', $leaveRequestId)
            ->whereNotNull('absence_date')
            ->whereNotNull('absence_reason')
            ->first();

        if (!$leaveRequest) {
            return Response::json([
                'message' => 'Failed to retrieve leave request.',
            ], 404);
        }

        $leaveRequest->delete();

        return Response::json([
            'message' => 'Leave request was successfully rejected.',
        ], 200);
    }

    public function bulkDestroy(Request $request)
    {
        // Define validation rules
        $rules = [
            'dtr_ids' => 'required|array',
            'dtr_ids.*' => 'required|exists:dtrs,dtr_id',
        ];
    
        // Validate the request
        $validator = Validator::make($request->all(), $rules);
    
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
            ], 422);
        }
    
        // Collect the unique DTR IDs to soft delete
        $dtrIds = $request->input('dtr_ids');
    
        // Perform the bulk soft delete within a transaction
        DB::transaction(function () use ($dtrIds) {
            DB::table('dtrs')
                ->whereIn('dtr_id', $dtrIds)
                ->update(['deleted_at' => Carbon::now()]);
        });
    
        // Return a success response
        return Response::json([
            'message' => 'Leave requests rejected successfully.',
        ], 200);
    }    
}
