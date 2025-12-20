<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserInvitationController extends Controller
{
    /**
     * Invite a new user to the tenant
     * Only owner or admin can invite users
     */
    public function invite(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Check permission
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to invite users',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'unique:users,email'],
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'in:Admin,Assistant,Sales,Finance'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create pending user
        $newUser = User::create([
            'email' => $request->email,
            'name' => $request->name ?? 'Pending User',
            'status' => User::STATUS_PENDING,
            'invited_by' => $user->id,
            'is_owner' => false,
            'profile_completed' => false,
        ]);

        // Generate invitation token
        $token = $newUser->generateInvitationToken();

        // TODO: Send invitation email
        // Mail::to($newUser->email)->send(new UserInvitation($newUser, $token));

        return response()->json([
            'success' => true,
            'message' => 'User invited successfully',
            'data' => [
                'user' => [
                    'id' => $newUser->id,
                    'email' => $newUser->email,
                    'name' => $newUser->name,
                    'status' => $newUser->status,
                    'invited_at' => $newUser->invited_at,
                ],
                'invitation_token' => $token, // Only for testing - remove in production
                // In production, this would be sent via email
            ],
        ], 201);
    }

    /**
     * Verify invitation token
     */
    public function verifyInvitation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('invitation_token', $request->token)
            ->where('status', User::STATUS_PENDING)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invitation token',
            ], 404);
        }

        if (!$user->hasValidInvitation()) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation has expired',
            ], 410);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'email' => $user->email,
                'name' => $user->name,
                'invited_at' => $user->invited_at,
                'expires_at' => $user->invited_at->addDays(7),
            ],
        ]);
    }

    /**
     * Complete user registration (accept invitation)
     */
    public function completeRegistration(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:20'],
            'title' => ['nullable', 'string', 'max:100'],
            'birth_year' => ['nullable', 'integer', 'min:1940', 'max:' . (date('Y') - 16)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('invitation_token', $request->token)
            ->where('status', User::STATUS_PENDING)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invitation token',
            ], 404);
        }

        if (!$user->hasValidInvitation()) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation has expired',
            ], 410);
        }

        // Update user with registration data
        $user->update([
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'title' => $request->title,
            'birth_year' => $request->birth_year,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'invitation_token' => null,
            'profile_completed' => true,
        ]);

        // Generate auth token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration completed successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'title' => $user->title,
                    'status' => $user->status,
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * Get list of invited users (pending and active)
     */
    public function listInvitations(): JsonResponse
    {
        $user = Auth::user();

        // Check permission
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view invitations',
            ], 403);
        }

        $users = User::with('inviter')
            ->where('is_owner', false)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'status' => $u->status,
                    'invited_by' => $u->inviter ? $u->inviter->name : null,
                    'invited_at' => $u->invited_at,
                    'is_expired' => $u->isPending() && !$u->hasValidInvitation(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'stats' => [
                    'total' => $users->count(),
                    'active' => $users->where('status', User::STATUS_ACTIVE)->count(),
                    'pending' => $users->where('status', User::STATUS_PENDING)->count(),
                ],
            ],
        ]);
    }

    /**
     * Resend invitation
     */
    public function resendInvitation(Request $request, $userId): JsonResponse
    {
        $currentUser = Auth::user();

        // Check permission
        if (!$currentUser->isOwner() && !$currentUser->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to resend invitations',
            ], 403);
        }

        $user = User::find($userId);

        if (!$user || $user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->status !== User::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'User has already completed registration',
            ], 400);
        }

        // Generate new invitation token
        $token = $user->generateInvitationToken();

        // TODO: Send invitation email
        // Mail::to($user->email)->send(new UserInvitation($user, $token));

        return response()->json([
            'success' => true,
            'message' => 'Invitation resent successfully',
            'data' => [
                'invitation_token' => $token, // Only for testing
            ],
        ]);
    }

    /**
     * Cancel/Delete invitation
     */
    public function cancelInvitation($userId): JsonResponse
    {
        $currentUser = Auth::user();

        // Check permission
        if (!$currentUser->isOwner() && !$currentUser->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to cancel invitations',
            ], 403);
        }

        $user = User::find($userId);

        if (!$user || $user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->status !== User::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel - user has already completed registration',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invitation cancelled successfully',
        ]);
    }
}
