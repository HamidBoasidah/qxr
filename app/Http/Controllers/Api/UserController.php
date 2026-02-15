<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Return all users except the authenticated user
     * Fields: id, first_name, last_name, avatar
     */
    public function others(Request $request)
    {
        $currentId = $request->user()->id;

        $users = User::where('id', '!=', $currentId)
            ->select(['id', 'first_name', 'last_name', 'avatar'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ], 200);
    }

    /**
     * Return users of type 'company'
     * Supports pagination via ?per_page
     */
    public function companies(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);

        $query = User::with(['companyProfile:id,user_id,company_name'])
            ->where('user_type', 'company')
            ->select(['id', 'first_name', 'last_name', 'avatar']);

        $paginated = $query->paginate($perPage);

        // map to include company_name if available
        $paginated->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar' => $user->avatar,
                'company_name' => $user->companyProfile->company_name ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $paginated,
        ], 200);
    }

    /**
     * Return users of type 'customer'
     * Supports pagination via ?per_page
     */
    public function customers(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);

        $query = User::where('user_type', 'customer')
            ->select(['id', 'first_name', 'last_name', 'avatar']);

        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar' => $user->avatar,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $paginated,
        ], 200);
    }
}
