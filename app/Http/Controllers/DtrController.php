<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; // Not used in this case, but remains for potential future modifications
use App\Models\Dtr;
use Illuminate\Support\Facades\Auth;

class DtrController extends Controller
{
    /**
     * Record time-in action.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function timeIn()
    {
        return $this->recordAction('time-in');
    }

    /**
     * Record break action.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function break()
    {
        return $this->recordAction('break');
    }

    /**
     * Record resume action.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resume()
    {
        return $this->recordAction('resume');
    }

    /**
     * Record time-out action.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function timeOut()
    {
        return $this->recordAction('time-out');
    }

    /**
     * Record the specified action for the authenticated user.
     *
     * @param string $actionType
     * @return \Illuminate\Http\JsonResponse
     */
    protected function recordAction($actionType)
    {
        $dtr = new Dtr();
        $dtr->user_id = Auth::id();
        $dtr->action_type = $actionType;
        $dtr->save();

        return response()->json(['message' => 'DTR entry recorded successfully'], 201);
    }
}
