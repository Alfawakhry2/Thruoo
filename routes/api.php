<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Modules\Sales\Api\TaxController;
use App\Http\Controllers\Modules\Sales\Api\LeadController;
use App\Http\Controllers\Modules\Sales\Api\TeamController;
use App\Http\Controllers\Modules\Sales\Api\ModuleController;
use App\Http\Controllers\Modules\Sales\Api\TargetController;
use App\Http\Controllers\Modules\Sales\Api\VendorController;
use App\Http\Controllers\Modules\Sales\Api\ProductController;
use App\Http\Controllers\Modules\Sales\Api\CategoryController;
use App\Http\Controllers\Modules\Sales\Api\ContractController;
use App\Http\Controllers\Modules\Sales\Api\CurrencyController;
use App\Http\Controllers\Modules\Sales\Api\AttributeController;
use App\Http\Controllers\Modules\Sales\Api\LeadSourceController;
use App\Http\Controllers\Modules\Sales\Api\LeadStatusController;
use App\Http\Controllers\Modules\Sales\Api\TenantAuthController;
use App\Http\Controllers\Modules\Sales\Api\ProductVariantController;
use App\Http\Controllers\Modules\Sales\Api\UserInvitationController;
use App\Http\Controllers\Sales\Products\Api\PaymentMethodController;
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
            Route::get('/personal-info', [AccountSettingsController::class, 'getPersonalInfo']);
            Route::put('/personal-info', [AccountSettingsController::class, 'updatePersonalInfo']);
            Route::patch('/personal-info', [AccountSettingsController::class, 'updatePersonalInfo']);

            // Update company details (owner/admin only)
            Route::get('/company-details', [AccountSettingsController::class, 'getCompanyInfo']);
            Route::put('/company-details', [AccountSettingsController::class, 'updateCompanyDetails']);
            Route::patch('/company-details', [AccountSettingsController::class, 'updateCompanyDetails']);

            // Upload avatar
            Route::post('personal-info/avatar', [AccountSettingsController::class, 'uploadAvatar']);

            // Upload company logo (owner/admin only)
            Route::post('company-info/logo', [AccountSettingsController::class, 'uploadLogo']);
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

        // ========================================
        // LEADS SYSTEM ROUTES WITH MODULE PREFIX
        // ========================================

        Route::prefix('modules/{moduleId}')->group(function () {

            // Lead Sources (Everyone can view, Owner/Admin can manage)
            Route::prefix('lead-sources')->group(function () {
                Route::get('/', [LeadSourceController::class, 'index']);
                Route::get('/all', [LeadSourceController::class, 'all']);
                Route::get('/{id}', [LeadSourceController::class, 'show']);
                Route::post('/', [LeadSourceController::class, 'store']);
                Route::put('/{id}', [LeadSourceController::class, 'update']);
                Route::delete('/{id}', [LeadSourceController::class, 'destroy']);
                Route::post('/batch-delete', [LeadSourceController::class, 'batchDelete']);
                Route::post('/{id}/toggle-status', [LeadSourceController::class, 'toggleStatus']);
            });

            // Lead Statuses (Everyone can view, Owner/Admin can manage)
            Route::prefix('lead-statuses')->group(function () {
                Route::get('/', [LeadStatusController::class, 'index']);
                Route::get('/all', [LeadStatusController::class, 'all']);
                Route::get('/{id}', [LeadStatusController::class, 'show']);
                Route::post('/', [LeadStatusController::class, 'store']);
                Route::put('/{id}', [LeadStatusController::class, 'update']);
                Route::delete('/{id}', [LeadStatusController::class, 'destroy']);
                Route::post('/reorder', [LeadStatusController::class, 'reorder']);
                Route::post('/batch-delete', [LeadStatusController::class, 'batchDelete']);
                Route::post('/{id}/toggle-status', [LeadStatusController::class, 'toggleStatus']);
            });

            // Leads Management
            // Leads Management
            Route::prefix('leads')->group(function () {
                // Statistics
                Route::get('/stats', [LeadController::class, 'stats']);

                // CRUD operations
                Route::get('/', [LeadController::class, 'index']);
                Route::post('/', [LeadController::class, 'store']);
                Route::get('/{leadId}', [LeadController::class, 'show']);
                Route::put('/{leadId}', [LeadController::class, 'update']);
                Route::delete('/{leadId}', [LeadController::class, 'destroy']);

                // Special actions
                Route::post('/{leadId}/reassign', [LeadController::class, 'reassign']);
                Route::post('/{leadId}/dismiss', [LeadController::class, 'dismiss']);
                Route::post('/{leadId}/convert', [LeadController::class, 'convert']);
                Route::post('/batch-delete', [LeadController::class, 'batchDelete']);
                Route::post('/batch-reassign', [LeadController::class, 'batchReassign']);

                // Contracts for specific lead
                Route::prefix('{leadId}/contracts')->group(function () {
                    Route::get('/', [ContractController::class, 'index']);
                    Route::post('/', [ContractController::class, 'store']);
                    Route::get('/{contractId}', [ContractController::class, 'show']);
                    Route::put('/{contractId}', [ContractController::class, 'update']);
                    Route::delete('/{contractId}', [ContractController::class, 'destroy']);
                });
            });
        });




        // Teams Management
        Route::prefix('teams')->group(function () {
            // CRUD operations (Owner/Admin only)
            Route::get('/', [TeamController::class, 'index']);
            Route::post('/', [TeamController::class, 'store']);
            Route::get('/{teamId}', [TeamController::class, 'show']);
            Route::put('/{teamId}', [TeamController::class, 'update']);
            Route::delete('/{teamId}', [TeamController::class, 'destroy']);

            // Team members management
            Route::post('/{teamId}/members', [TeamController::class, 'addMember']);
            Route::delete('/{teamId}/members/{userId}', [TeamController::class, 'removeMember']);

            // Team performance
            Route::get('/{teamId}/performance', [TeamController::class, 'performance']);

            // My teams (current user)
            Route::get('/my-teams', [TeamController::class, 'myTeams']);
        });

        // Targets Management
        Route::prefix('targets')->group(function () {
            // Statistics
            Route::get('/stats', [TargetController::class, 'stats']);

            // My targets (current user)
            Route::get('/my-targets', [TargetController::class, 'myTargets']);

            // CRUD operations (Owner/Admin only)
            Route::get('/', [TargetController::class, 'index']);
            Route::post('/', [TargetController::class, 'store']);
            Route::get('/{targetId}', [TargetController::class, 'show']);
            Route::put('/{targetId}', [TargetController::class, 'update']);
            Route::delete('/{targetId}', [TargetController::class, 'destroy']);

            // Refresh progress
            Route::post('/{targetId}/refresh', [TargetController::class, 'refreshProgress']);
        });

        // Categories
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/all', [CategoryController::class, 'all']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::get('/{categoryId}', [CategoryController::class, 'show']);
            Route::put('/{categoryId}', [CategoryController::class, 'update']);
            Route::delete('/{categoryId}', [CategoryController::class, 'destroy']);
            Route::post('/batch-delete', [CategoryController::class, 'batchDelete']);
            Route::post('/{categoryId}/assign-teams', [CategoryController::class, 'assignTeams']);
        });

        // Products
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store']);
            Route::get('/{productId}', [ProductController::class, 'show']);
            Route::put('/{productId}', [ProductController::class, 'update']);
            Route::delete('/{productId}', [ProductController::class, 'destroy']);
            Route::post('/{productId}/toggle-status', [ProductController::class, 'toggleStatus']);
            Route::post('/batch-delete', [ProductController::class, 'batchDelete']);

            // Product Variants
            Route::prefix('{productId}/variants')->group(function () {
                Route::get('/', [ProductVariantController::class, 'index']);
                Route::post('/', [ProductVariantController::class, 'store']);
                Route::get('/{variantId}', [ProductVariantController::class, 'show']);
                Route::put('/{variantId}', [ProductVariantController::class, 'update']);
                Route::delete('/{variantId}', [ProductVariantController::class, 'destroy']);
                Route::post('/{variantId}/toggle-status', [ProductVariantController::class, 'toggleStatus']);
            });
        });

        // Taxes
        Route::prefix('taxes')->group(function () {
            Route::get('/', [TaxController::class, 'index']);
            Route::get('/all', [TaxController::class, 'all']);
            Route::post('/', [TaxController::class, 'store']);
            Route::get('/{taxId}', [TaxController::class, 'show']);
            Route::put('/{taxId}', [TaxController::class, 'update']);
            Route::delete('/{taxId}', [TaxController::class, 'destroy']);
        });

        // Currencies
        Route::prefix('currencies')->group(function () {
            Route::get('/', [CurrencyController::class, 'index']);
            Route::get('/all', [CurrencyController::class, 'all']);
            Route::post('/', [CurrencyController::class, 'store']);
            Route::get('/{currencyId}', [CurrencyController::class, 'show']);
            Route::put('/{currencyId}', [CurrencyController::class, 'update']);
            Route::delete('/{currencyId}', [CurrencyController::class, 'destroy']);
            Route::post('/convert', [CurrencyController::class, 'convert']);
        });

        // Vendors
        Route::prefix('vendors')->group(function () {
            Route::get('/', [VendorController::class, 'index']);
            Route::get('/all', [VendorController::class, 'all']);
            Route::post('/', [VendorController::class, 'store']);
            Route::get('/{vendorId}', [VendorController::class, 'show']);
            Route::put('/{vendorId}', [VendorController::class, 'update']);
            Route::delete('/{vendorId}', [VendorController::class, 'destroy']);
        });

        // Attributes
        Route::prefix('attributes')->group(function () {
            Route::get('/', [AttributeController::class, 'index']);
            Route::get('/all', [AttributeController::class, 'all']);
            Route::post('/', [AttributeController::class, 'store']);
            Route::get('/{attributeId}', [AttributeController::class, 'show']);
            Route::put('/{attributeId}', [AttributeController::class, 'update']);
            Route::delete('/{attributeId}', [AttributeController::class, 'destroy']);

            // Attribute Values
            Route::post('/{attributeId}/values', [AttributeController::class, 'addValue']);
            Route::put('/{attributeId}/values/{valueId}', [AttributeController::class, 'updateValue']);
            Route::delete('/{attributeId}/values/{valueId}', [AttributeController::class, 'deleteValue']);
        });

        // Payment Methods
        Route::prefix('payment-methods')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'index']);
            Route::get('/all', [PaymentMethodController::class, 'all']);
            Route::post('/', [PaymentMethodController::class, 'store']);
            Route::get('/{paymentMethodId}', [PaymentMethodController::class, 'show']);
            Route::put('/{paymentMethodId}', [PaymentMethodController::class, 'update']);
            Route::delete('/{paymentMethodId}', [PaymentMethodController::class, 'destroy']);
        });



        // Modules Management (without moduleId prefix - for listing all modules)
        Route::prefix('modules')->group(function () {
            Route::get('/', [ModuleController::class, 'index']);
            Route::get('/all', [ModuleController::class, 'all']);
            Route::get('/{id}', [ModuleController::class, 'show']);
            // Note: No create/update/delete - modules are created during registration only
        });
    });
});
