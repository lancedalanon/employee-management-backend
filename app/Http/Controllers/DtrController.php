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
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->dtrService->index($perPage, $page);
        return $response;
    }

    public function show(int $dtrId)
    {
        $response = $this->dtrService->show($dtrId);
        return $response;
    }

    public function storeTimeIn()
    {
        $response = $this->dtrService->storeTimeIn();
        return $response;
    }

    public function storeBreak(int $dtrId)
    {
        $response = $this->dtrService->storeBreak($dtrId);
        return $response;
    }

    public function storeResume(int $dtrId)
    {
        $response = $this->dtrService->storeResume($dtrId);
        return $response;
    }

    public function storeTimeOut(StoreTimeOutRequest $request, $dtrId)
    {
        $validatedData = $request->validated();

        $response = $this->dtrService->storeTimeOut(
            $validatedData['end_of_the_day_report'],
            $dtrId,
            $request->file('end_of_the_day_report_images')
        );

        return $response;
    }
}
