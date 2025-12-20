<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SubdomainGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TenantRegistrationRequest;
use App\Models\Landlord\Tenant;
use App\Models\User;
use App\Services\Tenant\CreateTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class TenantRegistrationController extends Controller
{
    public function __construct(
        protected CreateTenantService $createTenantService
    ) {}

    /**
     * Get registration options (industries, staff counts, modules, etc.)
     * This helps frontend to populate dropdowns
     */
    public function getOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'industries' => Tenant::industryOptions(),
                'staff_counts' => Tenant::staffCountOptions(),
                'modules' => Tenant::availableModules(),
                'how_know_us' => User::howKnowUsOptions(),
                'titles' => User::titleOptions(),
                'roles' => [
                    'Admin' => 'Full access to all features',
                    'Assistant' => 'Can manage most features',
                    'Sales' => 'Sales focused access',
                    'Finance' => 'Finance focused access',
                ],
            ],
        ]);
    }

    /**
     * Check if subdomain is available
     */
    public function checkSubdomain(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subdomain' => ['required', 'string', 'min:3', 'max:63'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $subdomain = SubdomainGenerator::sanitize($request->subdomain);
        $isAvailable = SubdomainGenerator::isAvailable($subdomain);
        $error = $isAvailable ? null : SubdomainGenerator::getValidationError($subdomain);

        return response()->json([
            'success' => true,
            'data' => [
                'subdomain' => $subdomain,
                'available' => $isAvailable,
                'error' => $error,
                'suggestions' => !$isAvailable ? SubdomainGenerator::getSuggestions($request->subdomain) : [],
            ],
        ]);
    }

    /**
     * Generate subdomain suggestions from company name
     */
    public function suggestSubdomain(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $generated = SubdomainGenerator::generate($request->company_name);
        $suggestions = SubdomainGenerator::getSuggestions($request->company_name, 5);

        return response()->json([
            'success' => true,
            'data' => [
                'recommended' => $generated,
                'suggestions' => $suggestions,
            ],
        ]);
    }

    /**
     * Check if company name is available
     */
    public function checkCompanyName(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check exact match
        $exists = Tenant::on('mysql')
            ->where('name', $request->name)
            ->exists();

        // Also check similar names (case insensitive)
        $similar = Tenant::on('mysql')
            ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $request->name,
                'available' => !$exists && !$similar,
                'message' => ($exists || $similar) ? 'A company with this name already exists' : null,
            ],
        ]);
    }

    /**
     * Check if email is available
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $exists = Tenant::on('mysql')
            ->where('email', $request->email)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'email' => $request->email,
                'available' => !$exists,
                'message' => $exists ? 'This email is already registered' : null,
            ],
        ]);
    }

    /**
     * Validate step data without creating tenant
     */
    public function validateStep(Request $request): JsonResponse
    {
        $step = $request->input('step');
        $data = $request->input('data', []);

        $rules = $this->getStepValidationRules($step);

        if (empty($rules)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid step',
            ], 422);
        }

        $validator = Validator::make($data, $rules, $this->getValidationMessages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'step' => $step,
                'errors' => $validator->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'step' => $step,
            'message' => 'Step validation passed',
        ]);
    }

    /**
     * Get validation rules for a specific step
     */
    protected function getStepValidationRules(int $step): array
    {
        return match($step) {
            1 => [ // Personal Information
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:mysql.tenants,email'],
                'password' => ['required', 'min:8'],
                'password_confirmation' => ['required', 'same:password'],
                'phone' => ['required', 'string', 'max:20'],
                'title' => ['nullable', 'string', 'max:100'],
                'birth_year' => ['nullable', 'integer', 'min:1940', 'max:' . (date('Y') - 16)],
                'how_know_us' => ['nullable', 'array'],
            ],
            2 => [ // Company Information
                'name' => ['required', 'string', 'max:255'],
                'industry' => ['required', 'string', 'max:100'],
                'staff_count' => ['required', 'string', 'in:1-10,11-50,51-200,201-500,500+'],
                'website' => ['nullable', 'url', 'max:255'],
                'business_email' => ['nullable', 'email', 'max:255'],
                'country' => ['required', 'string', 'max:100'],
                'city' => ['required', 'string', 'max:100'],
                'address' => ['nullable', 'string', 'max:500'],
                'legal_id' => ['nullable', 'string', 'max:100'],
                'tax_id' => ['nullable', 'string', 'max:100'],
                'logo' => ['nullable', 'string'],
            ],
            3 => [ // Team Members (Optional)
                'team_members' => ['nullable', 'array', 'max:10'],
                'team_members.*.email' => ['required', 'email', 'distinct'],
                'team_members.*.name' => ['nullable', 'string', 'max:255'],
                'team_members.*.role' => ['nullable', 'string', 'in:Admin,Assistant,Sales,Finance'],
            ],
            4 => [ // Modules & Referral
                'modules' => ['required', 'array', 'min:1'],
                'modules.*' => ['string', 'in:sales'],
                'referral_code' => ['nullable', 'string', 'max:50'],
                'referral_relation' => ['nullable', 'string', 'max:255'],
            ],
            default => [],
        };
    }

    /**
     * Get validation messages
     */
    protected function getValidationMessages(): array
    {
        return [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password_confirmation.same' => 'Passwords do not match',
            'phone.required' => 'Phone number is required',
            'industry.required' => 'Industry is required',
            'staff_count.required' => 'Company size is required',
            'country.required' => 'Country is required',
            'city.required' => 'City is required',
            'modules.required' => 'Please select at least one module',
            'team_members.*.email.required' => 'Team member email is required',
            'team_members.*.email.distinct' => 'Team member emails must be unique',
        ];
    }

    /**
     * Register a new tenant (Full registration)
     */
    public function register(TenantRegistrationRequest $request): JsonResponse
    {
        try {
            $tenant = $this->createTenantService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Your account is ready.',
                'data' => [
                    'tenant' => [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'subdomain' => $tenant->subdomain,
                        'email' => $tenant->email,
                        'status' => $tenant->status,
                        'plan' => $tenant->plan,
                        'enabled_modules' => $tenant->enabled_modules,
                        'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
                        'remaining_trial_days' => $tenant->getRemainingTrialDays(),
                    ],
                    'urls' => [
                        'app' => $tenant->url,
                        'login' => $tenant->url . '/login',
                        'api' => $tenant->url . '/api',
                    ],
                    'next_steps' => [
                        'You can now log in to your new CRM',
                        'Team members will receive invitation emails',
                        'Explore the Sales module to get started',
                    ],
                ],
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify referral code
     */
    public function verifyReferralCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // For now, just validate format
        // TODO: Implement actual referral system
        $isValid = strlen($request->code) >= 4;

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $request->code,
                'valid' => $isValid,
                'discount' => $isValid ? '10%' : null,
                'message' => $isValid 
                    ? 'Referral code applied! You get 10% off your first paid plan.'
                    : 'Invalid referral code',
            ],
        ]);
    }
}
