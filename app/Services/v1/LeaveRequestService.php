<?php

namespace App\Services\v1;

use App\Models\Dtr;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class LeaveRequestService
{
    public function getLeaveRequests(Authenticatable $user, array $validatedData): JsonResponse
    {
        // Retrieve the query parameters from the request
        $sort = $validatedData['sort']; 
        $order = $validatedData['order']; 
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];

        // Build the query
        $query = Dtr::select('dtr_id', 'dtr_absence_date', 'dtr_absence_reason', 'dtr_absence_approved_at')
                    ->where('user_id', $user->user_id)
                    ->whereNotNull(['dtr_absence_date', 'dtr_absence_reason'])
                    ->withTrashed();

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('dtr_absence_reason', 'LIKE', "%$search%");
            });
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        // Paginate the results
        $leaveRequests = $query->paginate($perPage, ['*'], 'page', $page);

        // Construct the response data
        $responseData = [
            'message' => $leaveRequests->isEmpty() ? 'No leave requests found for the provided criteria.' : 'Leave requests retrieved successfully.',
            'data' => $leaveRequests,
        ];

        // Return the response as JSON with a 200 status code
        return response()->json($responseData, 200);
    }

    public function getLeaveRequestById(Authenticatable $user, int $leaveRequestId): JsonResponse
    {
        // Retrieve the leave request for the given ID and check if it exists
        $leaveRequest = Dtr::select('dtr_id', 'dtr_absence_date', 'dtr_absence_reason', 'dtr_absence_approved_at')
                            ->where('user_id', $user->user_id)
                            ->where('dtr_id', $leaveRequestId)
                            ->whereNotNull(['dtr_absence_date', 'dtr_absence_reason'])
                            ->withTrashed()
                            ->first();

        // Handle leave request not found
        if (!$leaveRequest) {
            return response()->json(['message' => 'Leave request not found.'], 404);
        }

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'Leave request retrieved successfully.',
            'data' => $leaveRequest,
        ], 200);
    }

    public function createLeaveRequest(Authenticatable $user, array $validatedData): JsonResponse
    {
        // TODO: further validation of leave request involving custom company settings for it

        // Create a new leave request for the current user with the provided data
        Dtr::create([
            'user_id' => $user->user_id,
            'dtr_absence_date' => $validatedData['dtr_absence_date'],
            'dtr_absence_reason' => $validatedData['dtr_absence_reason'],
        ]);

        // Return the response as JSON with a 201 status code
        return response()->json([
            'message' => 'Leave request created successfully.',
        ], 201);
    }

    public function deleteLeaveRequest(Authenticatable $user, int $leaveRequestId): JsonResponse
    {
        // Retrieve the leave request for the given ID and check if it exists
        $leaveRequest = Dtr::select('dtr_id', 'dtr_absence_date', 'dtr_absence_reason', 'dtr_absence_approved_at')
                        ->where('dtr_id', $leaveRequestId)
                        ->where('user_id', $user->user_id)
                        ->whereNotNull(['dtr_absence_date', 'dtr_absence_reason'])
                        ->whereNull('dtr_absence_approved_at')
                        ->first();

        // Handle leave request not found
        if (!$leaveRequest) {
            return response()->json(['message' => 'Leave request not found.'], 404);
        }

        // Soft delete the leave request
        $leaveRequest->delete();

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'Leave request deleted successfully.'
        ], 200);
    }
}