<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        
        // Check if user exists and has Spatie methods (admins only)
        $hasSpatie = $user && method_exists($user, 'getAllPermissions');
        
        return [
            ...parent::share($request),

            'locale' => fn () => app()->getLocale(),
            'dir'    => fn () => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',

            'auth' => [
                'user' => fn () => $user?->only(['id', 'name', 'email' , 'avatar' , 'locale']),
                'permissions' => fn () => $hasSpatie
                    ? $user->getAllPermissions()->pluck('name')->all()
                    : [],
                'roles' => fn () => $hasSpatie
                    ? $user->getRoleNames()->all()
                    : [],
            ],
        ];
    }
}
