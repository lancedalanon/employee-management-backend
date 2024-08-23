<?php

namespace App\Services\v1;

use App\Models\Dtr;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class DtrService
{
    public function getDtrs(Authenticatable $user, string $sort, string $order, ?string $search, int $perPage, int $page): JsonResponse
    {
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

    public function getDtrById(int $dtrId)
    {

    }

    public function createTimeIn(array $validatedData)
    {

    }

    public function createTimeOut(array $validatedData)
    {

    }

    public function createBreak()
    {
        
    }

    public function createResume()
    {
        
    }
}