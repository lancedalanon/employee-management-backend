<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; // Not used in this case, but remains for potential future modifications
use App\Models\Dtr;
use Illuminate\Support\Facades\Auth;

class DtrController extends Controller
{
    public function timeIn()
    {
        // Check if there's an existing time in for the current date
        $existingTimeIn = Dtr::where('user_id', Auth::id())
            ->whereDate('created_at', now())
            ->first();

        if ($existingTimeIn) {
            return response()->json(['error' => 'Time in already recorded for today'], 422);
        }

        // Create a new Dtr record
        $dtr = new Dtr;
        $dtr->user_id = Auth::id();
        $dtr->time_in = now()->format('H:i:s'); // Use current time
        $dtr->save();

        return response()->json(['message' => 'Time in recorded successfully'], 201);
    }

    public function timeOut()
    {
        // Check if there's an existing DTR for the current date
        $dtr = Dtr::where('user_id', Auth::id())
            ->whereDate('created_at', now())
            ->first();

        if (!$dtr) {
            return response()->json(['error' => 'No time in recorded yet'], 422);
        }

        // Check if there's already a time out recorded
        if (!is_null($dtr->time_out)) {
            return response()->json(['error' => 'Time out already recorded for today'], 422);
        }

        $dtr->time_out = now()->format('H:i:s'); // Use current time
        $dtr->save();

        return response()->json(['message' => 'Time out recorded successfully', 'total_work_hours' => $dtr->total_work_hours], 200);
    }
}
