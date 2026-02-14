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
}
