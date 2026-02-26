<?php

namespace App\Http\Controllers\Company\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\DTOs\UserDTO;

class ProfileController extends Controller
{

    /**
     * Display the authenticated company user's profile.
     */
    public function show(Request $request)
    {
        $user = Auth::guard('web')->user();

        // Load company profile relationship
        $user->load('companyProfile.category');

        $userDTO = UserDTO::fromModel($user)->toArray();

        return Inertia::render('Company/Profile/UserProfile', [
            'user' => $userDTO,
        ]);
    }

    /**
     * Update the company user's profile information.
     */
    public function update(ProfileUpdateRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::guard('web')->user();

        DB::transaction(function () use ($user, $validated, $request) {
            // Update User model
            $userFields = array_intersect_key($validated, array_flip([
                'first_name', 'last_name', 'email',
                'phone_number', 'whatsapp_number',
                'facebook', 'x_url', 'linkedin', 'instagram'
            ]));

            // Hash password if provided
            if (!empty($validated['password'])) {
                $userFields['password'] = Hash::make($validated['password']);
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // Upload new avatar
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $userFields['avatar'] = $avatarPath;
            }

            $user->update($userFields);

            // Update CompanyProfile
            if ($user->companyProfile) {
                $companyFields = array_intersect_key($validated, array_flip([
                    'company_name', 'category_id'
                ]));

                // Handle logo upload
                if ($request->hasFile('logo')) {
                    // Delete old logo if exists
                    if ($user->companyProfile->logo_path && Storage::disk('public')->exists($user->companyProfile->logo_path)) {
                        Storage::disk('public')->delete($user->companyProfile->logo_path);
                    }

                    // Upload new logo
                    $logoPath = $request->file('logo')->store('company-logos', 'public');
                    $companyFields['logo_path'] = $logoPath;
                }

                $user->companyProfile->update($companyFields);
            }
        });

        // Reload updated data
        $user->refresh();
        $user->load('companyProfile.category');

        // Update user in auth guard
        Auth::guard('web')->setUser($user);

        $userDTO = UserDTO::fromModel($user)->toArray();

        return Inertia::render('Company/Profile/UserProfile', [
            'user' => $userDTO,
        ])->with('success', 'Profile updated successfully');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = Auth::guard('web')->user();

        Auth::guard('web')->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
