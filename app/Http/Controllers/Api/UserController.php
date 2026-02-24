<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Traits\CanFilter;

class UserController extends Controller
{
    use CanFilter;
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

        $query = User::where('id', '!=', $currentId)
            ->select(['id', 'first_name', 'last_name', 'avatar']);

        // Apply filters (search, foreign keys, date, role-based)
        $query = $this->applyFilters(
            $query,
            $request,
            $this->getSearchableFields(),
            $this->getForeignKeyFilters()
        );

        $users = $query->get();

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
            ->select(['id', 'first_name', 'last_name', 'avatar', 'email']);

        // apply search + foreign key filters
        $query = $this->applyFilters(
            $query,
            $request,
            $this->getSearchableFields(),
            $this->getForeignKeyFilters()
        );

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
            ->select(['id', 'first_name', 'last_name', 'avatar', 'email']);

        // apply search + foreign key filters
        $query = $this->applyFilters(
            $query,
            $request,
            $this->getSearchableFields(),
            $this->getForeignKeyFilters()
        );

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

        /**
     * Fields that can be searched using CanFilter
     */
    protected function getSearchableFields(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
        ];
    }

    /**
     * Foreign key filters that CanFilter should map from request
     */
    protected function getForeignKeyFilters(): array
    {
        return [
            // example: allow filtering by user_type if needed (company/customer)
            'user_type' => 'user_type',
        ];
    }
    
}
