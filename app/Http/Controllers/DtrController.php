<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; // Not used in this case, but remains for potential future modifications
use App\Models\Dtr;
use App\Models\DtrBreak;
use Illuminate\Support\Facades\Auth;

class DtrController extends Controller
{
    public function timeIn()
    {
        // Create a new DTR entry for the authenticated user
        $dtr = new Dtr();
        $dtr->user_id = Auth::id();
        $dtr->time_in = now();
        $dtr->save();

        return response()->json(['message' => 'Time in recorded successfully'], 200);
    }

    public function break($dtrId)
    {
        // Find the DTR record
        $dtr = Dtr::findOrFail($dtrId);

        // Record the break time
        $dtrBreak = new DtrBreak();
        $dtrBreak->dtr_id = $dtr->id;
        $dtrBreak->break_time = now();
        $dtrBreak->save();

        return response()->json(['message' => 'Break started successfully'], 200);
    }

    public function resume($dtrId)
    {
        // Find the DTR record
        $dtr = Dtr::findOrFail($dtrId);

        // Find the last break record (assuming you're resuming the latest break)
        $latestBreak = $dtr->breaks()->latest()->first();

        // Update the resume time for the latest break
        $latestBreak->resume_time = now();
        $latestBreak->save();

        return response()->json(['message' => 'Break resumed successfully'], 200);
    }

    public function timeOut($dtrId)
    {
        // Find the DTR record
        $dtr = Dtr::findOrFail($dtrId);

        // Update the time_out field of the DTR record
        $dtr->time_out = now();
        $dtr->save();

        return response()->json(['message' => 'Time out recorded successfully'], 200);
    }
}
