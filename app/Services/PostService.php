<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostMedia;
use App\Models\PostTag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PostService
{
    public function index(int $perPage, int $page)
    {
        try {
            // Retrieve paginated post entries for the user
            $posts = Post::orderBy('post_id', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Return the success response with post data
            return Response::json([
                'message' => 'Posts retrieved successfully.',
                'current_page' => $posts->currentPage(),
                'data' => $posts->items(),
                'first_page_url' => $posts->url(1),
                'from' => $posts->firstItem(),
                'last_page' => $posts->lastPage(),
                'last_page_url' => $posts->url($posts->lastPage()),
                'links' => $posts->linkCollection()->toArray(),
                'next_page_url' => $posts->nextPageUrl(),
                'path' => $posts->path(),
                'per_page' => $posts->perPage(),
                'prev_page_url' => $posts->previousPageUrl(),
                'to' => $posts->lastItem(),
                'total' => $posts->total(),
            ], 200);
        } catch (\Exception $e) {
            // Return a generic error response
            return Response::json([
                'message' => 'An error occurred while retrieving the posts.',
            ], 500);
        }
    }

    public function show(int $postId)
    {
        try {
            // Retrieve the post entry for the given ID
            $post = Post::with(['tags', 'media'])
                ->where('post_id', $postId)
                ->first();

            if (!$post) {
                // Return a 404 Not Found response
                return Response::json([
                    'message' => 'Post not found.',
                ], 404);
            }

            // Return the success response with the Post entry data
            return Response::json([
                'message' => 'Post entry retrieved successfully.',
                'data' =>  $post
            ], 200);
        } catch (\Exception $e) {
            // Return a generic error response
            return Response::json([
                'message' => 'An error occurred while retrieving the post.',
            ], 500);
        }
    }

    public function store(array $validatedData, ?array $mediaFiles)
    {
        try {
            // Retrieve the authenticated user's ID
            $userId = Auth::id();

            // Create a new post entry with the provided data
            $post = Post::create([
                'post_title' => $validatedData['post_title'],
                'post_content' => $validatedData['post_content'],
                'user_id' => $userId,
            ]);

            // Iterate through each tag and save it to the post_tags table
            foreach ($validatedData['post_tags'] as $postTag) {
                PostTag::create([
                    'post_id' => $post->post_id,
                    'post_tag' => $postTag,
                ]);
            }

            // Process and save media files, update the post content with new URLs
            if ($mediaFiles) {
                foreach ($mediaFiles as $media) {
                    // Ensure the file is an instance of UploadedFile
                    if ($media instanceof UploadedFile) {
                        // Store the uploaded file in the public disk
                        $path = $media->store('post_media_files', 'public');

                        // Get the URL of the stored file
                        $url = Storage::disk('public')->url($path);

                        // Save the media file in the database
                        PostMedia::create([
                            'post_media' => $path,
                            'post_media_type' => $media->getClientMimeType(),
                            'post_id' => $post->post_id,
                        ]);

                        // Update the content with the new URL
                        $post->post_content = str_replace($media->getClientOriginalName(), $url, $post->post_content);
                    }
                }
                $post->save();
            }

            // Reload the post with its tags
            $post->load('tags');

            // Return the success response with the newly created post data
            return Response::json([
                'message' => 'Post created successfully.',
                'data' => $post,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while creating the post.',
            ], 500);
        }
    }

    public function update(array $validatedData, int $postId, ?array $mediaFiles)
    {
        try {
            // Find the post by ID
            $post = Post::where('post_id', $postId)
                ->first();

            // Return a 404 Not Found response
            if (!$post) {
                return Response::json([
                    'message' => 'Post not found.',
                ], 404);
            }

            // Update the post with the provided data
            $post->update([
                'post_title' => $validatedData['post_title'],
                'post_content' => $validatedData['post_content'],
            ]);

            // Delete existing tags
            PostTag::where('post_id', $postId)->delete();

            // Iterate through each tag and save it to the post_tags table
            foreach ($validatedData['post_tags'] as $postTag) {
                PostTag::create([
                    'post_id' => $post->post_id,
                    'post_tag' => $postTag,
                ]);
            }

            // Handle media files
            if ($mediaFiles) {
                // Delete existing media files if required
                $existingMedia = PostMedia::where('post_id', $postId)->get();
                foreach ($existingMedia as $media) {
                    Storage::disk('public')->delete($media->post_media); // Delete the file from storage
                    $media->delete(); // Delete the media record from the database
                }

                // Process and save new media files
                foreach ($mediaFiles as $media) {
                    // Ensure the file is an instance of UploadedFile
                    if ($media instanceof UploadedFile) {
                        // Store the uploaded file in the public disk
                        $path = $media->store('post_media_files', 'public');

                        // Get the URL of the stored file
                        $url = Storage::disk('public')->url($path);

                        // Save the media file in the database
                        PostMedia::create([
                            'post_media' => $path, // Store the file path
                            'post_media_type' => $media->getClientMimeType(),
                            'post_id' => $post->post_id,
                        ]);

                        // Update the content with the new URL
                        $post->post_content = str_replace($media->getClientOriginalName(), $url, $post->post_content);
                    }
                }
                $post->save();
            }

            // Reload the post with its tags
            $post->load('tags');

            // Return the success response with the newly updated post data
            return Response::json([
                'message' => 'Post updated successfully.',
                'data' => $post,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while updating the post.',
            ], 500);
        }
    }

    public function destroy(int $postId)
    {
        try {
            // Find the post by ID
            $post = Post::where('post_id', $postId)
                ->first();

            // Return a 404 Not Found response
            if (!$post) {
                return Response::json([
                    'message' => 'Post not found.',
                ], 404);
            }

            // Delete associated tags
            PostTag::where('post_id', $postId)->delete();

            // Delete the post
            $post->delete();

            // Return the success response
            return Response::json([
                'message' => 'Post deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while deleting the post.',
            ], 500);
        }
    }
}
