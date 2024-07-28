<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Services\PostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->postService->index($perPage, $page);
        return $response;
    }

    public function show(int $postId)
    {
        $response = $this->postService->show($postId);
        return $response;
    }

    public function store(StorePostRequest $request)
    {
        $validatedData = $request->validated();
        $response = $this->postService->store($validatedData, $request->file('post_media'));
        return $response;
    }

    public function update(UpdatePostRequest $request, int $postId)
    {
        $validatedData = $request->validated();
        $response = $this->postService->update($validatedData, $postId, $request->file('post_media'));
        return $response;
    }

    public function destroy(int $postId)
    {
        $response = $this->postService->destroy($postId);
        return $response;
    }
}
