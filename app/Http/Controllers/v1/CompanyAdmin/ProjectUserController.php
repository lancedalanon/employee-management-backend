<?php

namespace App\Http\Controllers\v1\CompanyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CompanyAdmin\ProjectController\IndexRequest;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectUserController extends Controller
{
    public function index(Authenticatable $user, IndexRequest $request, int $projectId): JsonResponse
    {
        // Retrieve validated data from request
        $validatedData = $request->validated();

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

    public function show(Authenticatable $user, int $projectId, int $userId): JsonResponse
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

    public function bulkAddUsers(Request $request, int $projectId): JsonResponse
    {
        // Validate the request to ensure 'project_users' is an array
        $validatedData = $request->validate([
            'project_users' => 'required|array',
            'project_users.*.user_id' => 'required|exists:users,user_id',
        ]);

        // Retrieve the authenticated user's company ID
        $companyId = $request->user()->company_id;

        // Initialize an array to hold the new ProjectUser entries
        $projectUsersData = [];

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Iterate over each project user data in the request
            foreach ($validatedData['project_users'] as $userData) {
                $user = User::find($userData['user_id']);

                // Check if the user's company_id matches the authenticated user's company_id
                if ($user->company_id !== $companyId) {
                    // Rollback the transaction before returning an error response
                    DB::rollBack();

                    // Return an error response indicating the mismatch
                    return response()->json([
                        'message' => "User ID {$userData['user_id']} does not belong to the same company.",
                    ], 422); // 422 Unprocessable Entity is a suitable status code for validation errors
                }

                // Prepare the data for insertion
                $projectUsersData[] = [
                    'project_id' => $projectId,
                    'user_id' => $userData['user_id'],
                    'company_id' => $companyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert the new ProjectUser records
            ProjectUser::insert($projectUsersData);

            // Commit the transaction
            DB::commit();

            // Return a success response
            return response()->json([
                'message' => 'Users added to project successfully.',
                'data' => $projectUsersData,
            ], 200);

        } catch (\Exception $e) {
            // Rollback the transaction if an error occurs
            DB::rollBack();

            // Return an error response
            return response()->json([
                'message' => 'Failed to add users to project.',
            ], 500);
        }
    }

    public function bulkRemoveUsers(Request $request, int $projectId): JsonResponse
    {
        // Validate the request to ensure 'project_users' is an array
        $validatedData = $request->validate([
            'project_users' => 'required|array',
            'project_users.*.user_id' => 'required|exists:project_users,user_id,project_id,' . $projectId,
        ]);
    
        // Retrieve the authenticated user's company ID
        $companyId = $request->user()->company_id;
    
        // Start a database transaction
        DB::beginTransaction();
    
        try {
            // Iterate over each project user data in the request
            foreach ($validatedData['project_users'] as $userData) {
                $projectUser = ProjectUser::where('user_id', $userData['user_id'])
                                          ->where('project_id', $projectId)
                                          ->first();
    
                // Check if the project user's company_id matches the authenticated user's company_id
                if ($projectUser->company_id !== $companyId) {
                    // Rollback the transaction before returning an error response
                    DB::rollBack();
    
                    // Return an error response indicating the mismatch
                    return response()->json([
                        'message' => "User ID {$userData['user_id']} does not belong to the same company or is not part of this project.",
                    ], 422);
                }
    
                // Delete the ProjectUser record
                $projectUser->delete();
            }
    
            // Commit the transaction
            DB::commit();
    
            // Return a success response
            return response()->json([
                'message' => 'Users removed from project successfully.',
            ], 200);
    
        } catch (\Exception $e) {
            // Rollback the transaction if an error occurs
            DB::rollBack();
    
            // Return an error response
            return response()->json([
                'message' => 'Failed to remove users from project.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }    
}
