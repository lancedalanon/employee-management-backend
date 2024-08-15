<?php

namespace App\Services;

use App\Models\Dtr;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class LeaveRequestService
{
    protected $cacheService;

    protected $user;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->user = Auth::user();
    }

    public function index(int $perPage, int $page)
    {
        try {
            $leaveRequests = Dtr::where('user_id', $this->user->user_id)
                ->whereNotNull('absence_date')
                ->whereNotNull('absence_reason')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

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
            return Response::json([
                'message' => 'An error occurred while retrieving the leave requests.',
            ], 500);
        }
    }

    public function show(int $leaveRequestId)
    {
        try {
            $leaveRequest = Dtr::where('dtr_id', $leaveRequestId)
                ->where('user_id', $this->user->user_id)
                ->whereNotNull('absence_date')
                ->whereNotNull('absence_reason')
                ->first();

            if (! $leaveRequest) {
                return Response::json([
                    'message' => 'Leave request not found.',
                ], 404);
            }

            return Response::json([
                'message' => 'Leave request retrieved successfully.',
                'data' => $leaveRequest,
            ], 200);
        } catch (\Exception $e) {
            return Response::json([
                'message' => 'An error occurred while retrieving the leave request.',
            ], 500);
        }
    }

    public function indexAdmin(int $perPage, int $page)
    {
        try {
            $leaveRequests = Dtr::whereNotNull('absence_date')
                ->whereNotNull('absence_reason')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

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
            return Response::json([
                'message' => 'An error occurred while retrieving the leave requests.',
            ], 500);
        }
    }

    public function showAdmin(int $leaveRequestId)
    {
        try {
            $leaveRequest = Dtr::where('dtr_id', $leaveRequestId)
                ->whereNotNull('absence_date')
                ->whereNotNull('absence_reason')
                ->first();

            if (! $leaveRequest) {
                return Response::json([
                    'message' => 'Leave request not found.',
                ], 404);
            }

            return Response::json([
                'message' => 'Leave request retrieved successfully.',
                'data' => $leaveRequest,
            ], 200);
        } catch (\Exception $e) {
            return Response::json([
                'message' => 'An error occurred while retrieving the leave request.',
            ], 500);
        }
    }

    public function bulkStore(array $validatedData)
    {
        try {
            $data = [];

            $startDate = Carbon::parse($validatedData['absence_start_date']);
            $endDate = Carbon::parse($validatedData['absence_end_date']);
            $absenceReason = $validatedData['absence_reason'];

            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                $data[] = [
                    'user_id' => $this->user->user_id,
                    'absence_date' => $date->format('Y-m-d'),
                    'absence_reason' => $absenceReason,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            $chunkSize = 500;
            DB::transaction(function () use ($data, $chunkSize) {
                foreach (array_chunk($data, $chunkSize) as $chunk) {
                    DB::table('dtrs')->insert($chunk);
                }
            });

            return Response::json([
                'message' => 'Leave requests stored successfully',
            ], 201);
        } catch (\Exception $e) {
            return Response::json([
                'message' => 'An error occurred while storing the leave requests.',
            ], 500);
        }
    }

    public function update(int $leaveRequestId)
    {
        try {
            $leaveRequest = Dtr::where('dtr_id', $leaveRequestId)
                ->whereNotNull('absence_date')
                ->whereNotNull('absence_reason')
                ->whereNull('absence_approved_at')
                ->first();

            if (! $leaveRequest) {
                return Response::json([
                    'message' => 'Failed to retrieve leave request.',
                ], 404);
            }

            $leaveRequest->absence_approved_at = Carbon::now();
            $leaveRequest->updated_at = Carbon::now();
            $leaveRequest->save();

            return Response::json([
                'message' => 'Leave request was successfully approved.',
            ], 200);
        } catch (\Exception $e) {
            return Response::json([
                'message' => 'An error occurred while approving the leave request.',
            ], 500);
        }
    }

    public function bulkUpdate(array $validatedData)
    {
        try {
            $dtrIds = $validatedData['dtr_ids'];

            DB::transaction(function () use ($dtrIds) {
                DB::table('dtrs')
                    ->whereIn('dtr_id', $dtrIds)
                    ->update([
                        'absence_approved_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
            });

            return Response::json([
                'message' => 'Leave requests updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return Response::json([
                'message' => 'An error occurred while approving the leave requests.',
            ], 500);
        }
    }

    public function destroy(int $leaveRequestId)
    {
        $leaveRequest = Dtr::where('dtr_id', $leaveRequestId)
            ->whereNotNull('absence_date')
            ->whereNotNull('absence_reason')
            ->first();

        if (! $leaveRequest) {
            return Response::json([
                'message' => 'Failed to retrieve leave request.',
            ], 404);
        }

        $leaveRequest->delete();

        return Response::json([
            'message' => 'Leave request was successfully rejected.',
        ], 200);
    }

    public function bulkDestroy(array $validatedData)
    {
        $dtrIds = $validatedData['dtr_ids'];

        DB::transaction(function () use ($dtrIds) {
            DB::table('dtrs')
                ->whereIn('dtr_id', $dtrIds)
                ->update([
                    'deleted_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
        });

        return Response::json([
            'message' => 'Leave requests rejected successfully.',
        ], 200);
    }
}
