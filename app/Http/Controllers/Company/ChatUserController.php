<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\DTOs\UserDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    /**
     * Get users for chat conversation creation
     * This endpoint is accessible to all authenticated users
     */
    public function index(Request $request, UserService $userService)
    {
        $perPage = (int) $request->input('per_page', 50);
        $search = $request->input('search');
        $currentUserId = Auth::guard('web')->id();

        // Get users excluding current user
        $users = $userService->search($search, $perPage);

        // Transform to DTO and filter out current user
        $filteredUsers = $users->getCollection()->filter(function ($user) use ($currentUserId) {
            return $user->id !== $currentUserId;
        })->map(function ($user) {
            return UserDTO::fromModel($user)->toIndexArray();
        })->values(); // Reset array keys

        // Replace the collection with filtered data
        $users->setCollection($filteredUsers);

        return response()->json($users);
    }
}
