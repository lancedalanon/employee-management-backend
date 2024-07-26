<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dtr\StoreTimeOutRequest;
use Illuminate\Http\Request;
use App\Models\Dtr;
use App\Models\DtrBreak;
use App\Models\EndOfTheDayReportImage;
use App\Services\DtrService;
use Illuminate\Support\Facades\Auth;
use App\Services\User\UserRoleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class DtrController extends Controller
{
    protected $dtrService;

    public function __construct(DtrService $dtrService)
    {
        $this->dtrService = $dtrService;
    }

    public function index(Request $request)
    {
        // Set up pagination parameters with defaults
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        try {
            $response = $this->dtrService->index($perPage, $page);
            return $response;
        } catch (\Exception $e) {
            return Response::json([
                'message' => 'An error occurred while retrieving the DTR entries.',
            ], 500);
        }
    }

    public function show(int $dtrId)
    {
        try {
            $response = $this->dtrService->show($dtrId);
            return $response;
        } catch (\Exception $e) {
            return Response::json([
                'message' => 'An error occurred while retrieving the DTR entry.',
            ], 500);
        }
    }

    public function storeTimeIn()
    {
        try {
            $response = $this->dtrService->storeTimeIn();
            return $response;
        } catch (\Exception $e) {
            return Response::json([
                'message' => 'An error occurred while recording the time in.',
            ], 500);
        }
    }

    public function storeBreak(int $dtrId)
    {
        try {
            $response = $this->dtrService->storeBreak($dtrId);
            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while starting the break.',
            ], 500);
        }
    }

    public function storeResume(int $dtrId)
    {
        try {
            $response = $this->dtrService->storeResume($dtrId);
            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while resuming the break.',
            ], 500);
        }
    }

    public function storeTimeOut(StoreTimeOutRequest $request, $dtrId)
    {
        $validatedData = $request->validated();

        try {
            // Pass the validated data and uploaded images to the service
            $response = $this->dtrService->storeTimeOut(
                $validatedData['end_of_the_day_report'],
                $dtrId,
                $request->file('end_of_the_day_report_images')
            );

            return $response;
        } catch (\Exception $e) {
            return Response::json([
                'message' => 'An error occurred while recording the time out.',
            ], 500);
        }
    }
}
