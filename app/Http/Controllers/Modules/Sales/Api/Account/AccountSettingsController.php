<?php

namespace App\Http\Controllers\Modules\Sales\Api\Account;


use Illuminate\Http\Request;
use App\Models\Landlord\Tenant;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Api\Account\UpdatePersonalInfoRequest;
use App\Http\Requests\Api\Account\UpdateCompanyDetailsRequest;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class AccountSettingsController extends Controller
{
    /**
     * Get current user's personal info and company details
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $tenant = SpatieTenant::current();

        return response()->json([
            'success' => true,
            'data' => [
                'personal_info' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'title' => $user->title,
                    'birth_year' => $user->birth_year,
                    'how_know_us' => $user->how_know_us,
                    'avatar' => $user->avatar_url,
                ],
                'company_details' => [
                    'company_name' => $tenant->name,
                    'city' => $tenant->city,
                    'country' => $tenant->country,
                    'industry' => $tenant->industry,
                    'website' => $tenant->website,
                    'company_phone' => $tenant->phone,
                    'company_whatsapp' => $tenant->settings['whatsapp'] ?? null,
                    'address' => $tenant->address,
                    'business_email' => $tenant->business_email,
                    'legal_id' => $tenant->legal_id,
                    'tax_id' => $tenant->tax_id,
                    'facebook' => $tenant->settings['facebook'] ?? null,
                    'instagram' => $tenant->settings['instagram'] ?? null,
                    'linkedin' => $tenant->settings['linkedin'] ?? null,
                    'snapchat' => $tenant->settings['snapchat'] ?? null,
                    'tiktok' => $tenant->settings['tiktok'] ?? null,
                    'youtube' => $tenant->settings['youtube'] ?? null,
                    'logo' => $tenant->logo,
                ],
            ],
        ]);
    }

    /**
 * Get current user's personal information
 */
public function getPersonalInfo(): JsonResponse
{
    $user = Auth::user();

    return response()->json([
        'success' => true,
        'data' => [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'title' => $user->title,
            'birth_year' => $user->birth_year,
            'how_know_us' => $user->how_know_us,
            'avatar' => $user->avatar_url,
        ],
    ]);
}

/**
 * Get current tenant company details
 */
public function getCompanyInfo(): JsonResponse
{
    $tenant = SpatieTenant::current();

    return response()->json([
        'success' => true,
        'data' => [
            'company_name' => $tenant->name,
            'city' => $tenant->city,
            'country' => $tenant->country,
            'industry' => $tenant->industry,
            'website' => $tenant->website,
            'company_phone' => $tenant->phone,
            'company_whatsapp' => $tenant->settings['whatsapp'] ?? null,
            'address' => $tenant->address,
            'business_email' => $tenant->business_email,
            'legal_id' => $tenant->legal_id,
            'tax_id' => $tenant->tax_id,
            'facebook' => $tenant->settings['facebook'] ?? null,
            'instagram' => $tenant->settings['instagram'] ?? null,
            'linkedin' => $tenant->settings['linkedin'] ?? null,
            'snapchat' => $tenant->settings['snapchat'] ?? null,
            'tiktok' => $tenant->settings['tiktok'] ?? null,
            'youtube' => $tenant->settings['youtube'] ?? null,
            'logo' => $tenant->logo_url,
        ],
    ]);
}

    /**
     * Update personal information
     */
    public function updatePersonalInfo(UpdatePersonalInfoRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        // Handle password update separately
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $avatarPath;
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Personal information updated successfully',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'title' => $user->title,
                'birth_year' => $user->birth_year,
                'how_know_us' => $user->how_know_us,
                'avatar' => $user->avatar_url,
            ],
        ]);
    }

    /**
     * Update company details
     * Only owner or admin can update company details
     */
    public function updateCompanyDetails(UpdateCompanyDetailsRequest $request): JsonResponse
    {
        $user = Auth::user();

        // Check if user has permission to update company details
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update company details',
            ], 403);
        }

        $tenant = SpatieTenant::current();
                $data = $request->validated();

        // Separate main fields from social media fields
        $mainFields = [
            'name' => $data['company_name'] ?? $tenant->name,
            'city' => $data['city'] ?? $tenant->city,
            'country' => $data['country'] ?? $tenant->country,
            'industry' => $data['industry'] ?? $tenant->industry,
            'website' => $data['website'] ?? $tenant->website,
            'phone' => $data['company_phone'] ?? $tenant->phone,
            'address' => $data['address'] ?? $tenant->address,
            'business_email' => $data['business_email'] ?? $tenant->business_email,
            'legal_id' => $data['legal_id'] ?? $tenant->legal_id,
            'tax_id' => $data['tax_id'] ?? $tenant->tax_id,
        ];

        // Handle social media and additional settings
        $settings = $tenant->settings ?? [];
        $settings['whatsapp'] = $data['company_whatsapp'] ?? $settings['whatsapp'] ?? null;
        $settings['facebook'] = $data['facebook'] ?? $settings['facebook'] ?? null;
        $settings['instagram'] = $data['instagram'] ?? $settings['instagram'] ?? null;
        $settings['linkedin'] = $data['linkedin'] ?? $settings['linkedin'] ?? null;
        $settings['snapchat'] = $data['snapchat'] ?? $settings['snapchat'] ?? null;
        $settings['tiktok'] = $data['tiktok'] ?? $settings['tiktok'] ?? null;
        $settings['youtube'] = $data['youtube'] ?? $settings['youtube'] ?? null;

        $mainFields['settings'] = $settings;

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($tenant->logo) {
                Storage::disk('public')->delete($tenant->logo);
            }

            $logoPath = $request->file('logo')->store('logos', 'public');
            $mainFields['logo'] = $logoPath;
        }

        // Update tenant in landlord database
        Tenant::on('mysql')
            ->where('id', $tenant->id)
            ->update($mainFields);

        // Refresh tenant data
        $tenant->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Company details updated successfully',
            'data' => [
                'company_name' => $tenant->name,
                'city' => $tenant->city,
                'country' => $tenant->country,
                'industry' => $tenant->industry,
                'website' => $tenant->website,
                'company_phone' => $tenant->phone,
                'company_whatsapp' => $tenant->settings['whatsapp'] ?? null,
                'address' => $tenant->address,
                'business_email' => $tenant->business_email,
                'legal_id' => $tenant->legal_id,
                'tax_id' => $tenant->tax_id,
                'facebook' => $tenant->settings['facebook'] ?? null,
                'instagram' => $tenant->settings['instagram'] ?? null,
                'linkedin' => $tenant->settings['linkedin'] ?? null,
                'snapchat' => $tenant->settings['snapchat'] ?? null,
                'tiktok' => $tenant->settings['tiktok'] ?? null,
                'youtube' => $tenant->settings['youtube'] ?? null,
                'logo' => $tenant->logo,
            ],
        ]);
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'], // Max 2MB
        ]);

        $user = Auth::user();

        // Delete old avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $avatarPath = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $avatarPath]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded successfully',
            'data' => [
                'avatar' => $user->avatar_url,
            ],
        ]);
    }

    /**
     * Upload company logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'], // Max 2MB
        ]);

        $user = Auth::user();

        // Check permission
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update company logo',
            ], 403);
        }

        $tenant = SpatieTenant::current();
        // Delete old logo if exists
        if ($tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
        }

        $logoPath = $request->file('logo')->store('logos', 'public');

        Tenant::on('mysql')
            ->where('id', $tenant->id)
            ->update(['logo' => $logoPath]);

        return response()->json([
            'success' => true,
            'message' => 'Company logo uploaded successfully',
            'data' => [
                'logo' => asset('storage/' . $logoPath),
            ],
        ]);
    }
}
