<?php

namespace App\Services\v1;

use App\Models\ProjectUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class ProjectUserService
{
    public function getProjectUsers(Authenticatable $user, array $validatedData, int $projectId): JsonResponse
    {
        // Retrieve the query parameters from the request
        $sort = $validatedData['sort'];
        $order = $validatedData['order']; 
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];

        // Retrieve project users based on the given project ID and parameters
        $query = ProjectUser::with(['user:user_id,username,first_name,middle_name,last_name,suffix'])
                    ->where('project_id', $projectId)
                    ->where('company_id', $user->company_id)
                    ->select(['user_id', 'project_role']);

        // Apply search filter if provided
        if ($search) {
            $words = explode(' ', $search);

            $query->whereHas('user', function ($query) use ($words) {
                foreach ($words as $word) {
                    $query->where(function ($query) use ($word) {
                        $query->where('username', 'LIKE', "%$word%")
                            ->orWhere('first_name', 'LIKE', "%$word%")
                            ->orWhere('middle_name', 'LIKE', "%$word%")
                            ->orWhere('last_name', 'LIKE', "%$word%")
                            ->orWhere('suffix', 'LIKE', "%$word%");
                    });
                }
            });
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        // Paginate the results
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform the data to exclude 'user_id' from the 'user' relation
        $transformedUsers = $users->map(function ($projectUser) {
            return [
                'user_id' => $projectUser->user_id,
                'project_role' => $projectUser->project_role,
                'username' => $projectUser->user->username,
                'full_name' => $projectUser->user->full_name,
            ];
        });

        // Replace the original collection with the transformed one
        $users->setCollection($transformedUsers);

        // Construct the response data
        $responseData = [
            'message' => $transformedUsers->isEmpty() ? 'No project users found for the provided criteria.' : 'Project users retrieved successfully.',
            'data' => $users, // Return the paginated result
        ];

        // Return the response as JSON with a 200 status code
        return response()->json($responseData, 200);
    }

    public function getProjectUsersById(Authenticatable $user, int $projectId, int $userId): JsonResponse
    {
        // Retrieve the User for the given ID and check if it exists
        $user = ProjectUser::with(['user:user_id,username,first_name,middle_name,last_name,suffix'])
                ->where('user_id', $userId)
                ->where('project_id', $projectId)
                ->where('company_id', $user->company_id)
                ->select(['user_id', 'project_role'])
                ->first();

        // Handle Project User not found
        if (!$user) {
            return response()->json(['message' => 'Project user not found.'], 404);
        }

        // Format the data to include the necessary fields
        $formattedUser = [
            'user_id' => $user->user_id,
            'project_role' => $user->project_role,
            'username' => $user->user->username,
            'full_name' => $user->user->full_name,
        ];

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'Project user retrieved successfully.',
            'data' => $formattedUser,
        ], 200);
    }
}