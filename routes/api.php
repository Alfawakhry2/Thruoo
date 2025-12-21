<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Modules\Sales\Api\ModuleController;
use App\Http\Controllers\Modules\Sales\Api\LeadSourceController;
use App\Http\Controllers\Modules\Sales\Api\LeadStatusController;
use App\Http\Controllers\Modules\Sales\Api\TenantAuthController;
use App\Http\Controllers\Modules\Sales\Api\UserInvitationController;
use App\Http\Controllers\Modules\Sales\Api\TenantRegistrationController;
use App\Http\Controllers\Modules\Sales\Api\Account\AccountSettingsController;


/*
|--------------------------------------------------------------------------
| Landlord Routes (No tenant resolution)
|--------------------------------------------------------------------------
*/

Route::prefix('registration')->group(function () {
    // Get registration options (industries, modules, etc.)
    Route::get('/options', [TenantRegistrationController::class, 'getOptions']);

    // Validation endpoints
    Route::post('/check-subdomain', [TenantRegistrationController::class, 'checkSubdomain']);
    Route::post('/check-company-name', [TenantRegistrationController::class, 'checkCompanyName']);
    Route::post('/check-email', [TenantRegistrationController::class, 'checkEmail']);
    Route::post('/suggest-subdomain', [TenantRegistrationController::class, 'suggestSubdomain']);
    Route::post('/verify-referral', [TenantRegistrationController::class, 'verifyReferralCode']);

    // Step validation
    Route::post('/validate-step', [TenantRegistrationController::class, 'validateStep']);

    // Final registration
    Route::post('/register', [TenantRegistrationController::class, 'register']);
});

// Legacy route (keep for backward compatibility)
Route::post('/tenants/register', [TenantRegistrationController::class, 'register']);

/*
|--------------------------------------------------------------------------
| Tenant Routes (Requires tenant resolution)
|--------------------------------------------------------------------------
*/

Route::middleware(['resolve.tenant', 'ensure.subscription'])->group(function () {
    // Authentication routes
    Route::post('/auth/login', [TenantAuthController::class, 'login']);

    // User Invitation Routes (Public - no auth required)
    Route::prefix('invitations')->group(function () {
        Route::post('/verify', [UserInvitationController::class, 'verifyInvitation']);
        Route::post('/complete', [UserInvitationController::class, 'completeRegistration']);
    });

    // Authenticated routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/auth/me', [TenantAuthController::class, 'me']);
        Route::post('/auth/logout', [TenantAuthController::class, 'logout']);

        // Account Settings Routes
        Route::prefix('account')->group(function () {
            // Get all account settings
            Route::get('/settings', [AccountSettingsController::class, 'index']);

            // Update personal information
            Route::put('/personal-info', [AccountSettingsController::class, 'updatePersonalInfo']);
            Route::patch('/personal-info', [AccountSettingsController::class, 'updatePersonalInfo']);

            // Update company details (owner/admin only)
            Route::put('/company-details', [AccountSettingsController::class, 'updateCompanyDetails']);
            Route::patch('/company-details', [AccountSettingsController::class, 'updateCompanyDetails']);

            // Upload avatar
            Route::post('/avatar', [AccountSettingsController::class, 'uploadAvatar']);

            // Upload company logo (owner/admin only)
            Route::post('/logo', [AccountSettingsController::class, 'uploadLogo']);
        });

        // User Invitation Management Routes (Owner/Admin only)
        Route::prefix('invitations')->group(function () {
            Route::get('/', [UserInvitationController::class, 'listInvitations']);
            Route::post('/', [UserInvitationController::class, 'invite']);
            Route::post('/{userId}/resend', [UserInvitationController::class, 'resendInvitation']);
            Route::delete('/{userId}', [UserInvitationController::class, 'cancelInvitation']);
        });

        // ========================================
        // LEADS SYSTEM ROUTES
        // ========================================

        // Modules Management (Owner/Admin only)
        Route::prefix('modules')->group(function () {
            Route::get('/', [ModuleController::class, 'index']);
            Route::get('/all', [ModuleController::class, 'all']);
            Route::post('/', [ModuleController::class, 'store']);
            Route::get('/{id}', [ModuleController::class, 'show']);
            Route::put('/{id}', [ModuleController::class, 'update']);
            Route::delete('/{id}', [ModuleController::class, 'destroy']);
            Route::post('/{id}/toggle-status', [ModuleController::class, 'toggleStatus']);
        });

        // Lead Sources
        Route::prefix('lead-sources')->group(function () {
            // Everyone can view
            Route::get('/', [LeadSourceController::class, 'index']);
            Route::get('/all', [LeadSourceController::class, 'all']);
            Route::get('/{id}', [LeadSourceController::class, 'show']);

            // Owner/Admin only
            Route::post('/', [LeadSourceController::class, 'store']);
            Route::put('/{id}', [LeadSourceController::class, 'update']);
            Route::delete('/{id}', [LeadSourceController::class, 'destroy']);
            Route::post('/batch-delete', [LeadSourceController::class, 'batchDelete']);
            Route::post('/{id}/toggle-status', [LeadSourceController::class, 'toggleStatus']);
        });

        // Lead Statuses
        Route::prefix('lead-statuses')->group(function () {
            // Everyone can view
            Route::get('/', [LeadStatusController::class, 'index']);
            Route::get('/all', [LeadStatusController::class, 'all']);
            Route::get('/{id}', [LeadStatusController::class, 'show']);

            // Owner/Admin only
            Route::post('/', [LeadStatusController::class, 'store']);
            Route::put('/{id}', [LeadStatusController::class, 'update']);
            Route::delete('/{id}', [LeadStatusController::class, 'destroy']);
            Route::post('/reorder', [LeadStatusController::class, 'reorder']);
            Route::post('/batch-delete', [LeadStatusController::class, 'batchDelete']);
            Route::post('/{id}/toggle-status', [LeadStatusController::class, 'toggleStatus']);
        });

        // Leads Management
        Route::prefix('leads')->group(function () {
            // Statistics
            Route::get('/stats', [LeadController::class, 'stats']);

            // CRUD operations
            Route::get('/', [LeadController::class, 'index']);
            Route::post('/', [LeadController::class, 'store']);
            Route::get('/{id}', [LeadController::class, 'show']);
            Route::put('/{id}', [LeadController::class, 'update']);
            Route::delete('/{id}', [LeadController::class, 'destroy']);

            // Special actions
            Route::post('/{id}/assign', [LeadController::class, 'assign']);
            Route::post('/{id}/convert', [LeadController::class, 'convert']);
            Route::post('/batch-delete', [LeadController::class, 'batchDelete']);
            Route::post('/batch-assign', [LeadController::class, 'batchAssign']);
        });
    });
});
