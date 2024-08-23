<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\DtrController\IndexRequest;
use App\Http\Requests\v1\DtrController\StoreTimeInRequest;
use App\Http\Requests\v1\DtrController\StoreTimeOutRequest;
use App\Services\v1\DtrService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DtrController extends Controller
{
    protected DtrService $dtrService;
    protected Authenticatable $user;

    public function __construct(DtrService $dtrService)
    {
        $this->dtrService = $dtrService;
        $this->user = Auth::user();
    }

    public function index(IndexRequest $request): JsonResponse
    {
        // Get validated data
        $validatedData = $request->validated();
    
        // Retrieve DTR records based on the given parameters
        return $this->dtrService->getDtrs($this->user, $validatedData);
    }

    public function show(int $dtrId): JsonResponse
    {
        // Retrieve DTR record by ID based on the given parameters
        return $this->dtrService->getDtrById($this->user, $dtrId);
    }

    public function storeTimeIn(StoreTimeInRequest $request): JsonResponse
    {
        // Get validated data
        $validatedData = $request->validated();

        // Handle DTR time-in image file upload
        return $this->dtrService->createTimeIn($this->user, $validatedData);
    }

    public function storeTimeOut(StoreTimeOutRequest $request): JsonResponse
    {
        // Get validated data
        $validatedData = $request->validated();

        // Handle DTR time-out image file upload
        return $this->dtrService->createTimeOut($this->user, $validatedData);
    }

    public function storeBreak(): JsonResponse
    {
        // Handle DTR break
        return $this->dtrService->createBreak($this->user);
    }    

    public function storeResume(): JsonResponse
    {
        // Handle DTR resume
        return $this->dtrService->createResume($this->user);
    }    
}
