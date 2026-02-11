<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get authenticated user from web guard
        $user = Auth::guard('web')->user();
        
        // If no user, redirect to login (this should be handled by auth:web middleware)
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check if user_type is 'company'
        if ($user->user_type !== 'company') {
            abort(403, 'Access denied. This area is for companies only.');
        }
        
        return $next($request);
    }
}
