<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The connection name for the model.
     * Uses landlord connection (mysql)
     */
    protected $connection = 'mysql';

    /**
     * The table associated with the model.
     */
    protected $table = 'tenants';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // Basic Info
        'name',
        'subdomain',
        'domain',
        'database',
        
        // Contact Info
        'email',
        'phone',
        'business_email',
        
        // Company Details
        'industry',
        'staff_count',
        'website',
        'country',
        'city',
        'address',
        
        // Legal Info
        'legal_id',
        'tax_id',
        
        // Branding
        'logo',
        
        // Referral Info
        'referral_code',
        'referral_relation',
        
        // Subscription & Status
        'status',
        'trial_ends_at',
        'subscription_ends_at',
        'plan',
        'enabled_modules',
        
        // Settings & Meta
        'settings',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'enabled_modules' => 'array',
            'settings' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Available status values
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Available plan values
     */
    const PLAN_TRIAL = 'trial';
    const PLAN_STARTER = 'starter';
    const PLAN_PROFESSIONAL = 'professional';
    const PLAN_ENTERPRISE = 'enterprise';

    /**
     * Available modules
     */
    const MODULE_SALES = 'sales';
    const MODULE_CONTACTS = 'contacts';
    const MODULE_ACCOUNTING = 'accounting';
    const MODULE_INVENTORY = 'inventory';
    const MODULE_HR = 'hr';

    /**
     * Get all available modules
     */
    public static function availableModules(): array
    {
        return [
            self::MODULE_SALES => [
                'name' => 'Sales',
                'description' => 'Manage leads, deals, proposals, and invoices',
                'icon' => 'chart-line',
                'available' => true,
            ],
            self::MODULE_CONTACTS => [
                'name' => 'Contacts',
                'description' => 'Customer and supplier management',
                'icon' => 'users',
                'available' => false, // Coming soon
            ],
            self::MODULE_ACCOUNTING => [
                'name' => 'Accounting',
                'description' => 'Financial management and reporting',
                'icon' => 'calculator',
                'available' => false, // Coming soon
            ],
            self::MODULE_INVENTORY => [
                'name' => 'Inventory',
                'description' => 'Stock and warehouse management',
                'icon' => 'box',
                'available' => false, // Coming soon
            ],
            self::MODULE_HR => [
                'name' => 'HR',
                'description' => 'Human resources management',
                'icon' => 'briefcase',
                'available' => false, // Coming soon
            ],
        ];
    }

    /**
     * Get available staff count options
     */
    public static function staffCountOptions(): array
    {
        return [
            '1-10' => '1-10 employees',
            '11-50' => '11-50 employees',
            '51-200' => '51-200 employees',
            '201-500' => '201-500 employees',
            '500+' => '500+ employees',
        ];
    }

    /**
     * Get available industry options
     */
    public static function industryOptions(): array
    {
        return [
            'technology' => 'Technology',
            'healthcare' => 'Healthcare',
            'finance' => 'Finance & Banking',
            'retail' => 'Retail & E-commerce',
            'manufacturing' => 'Manufacturing',
            'education' => 'Education',
            'real_estate' => 'Real Estate',
            'consulting' => 'Consulting',
            'marketing' => 'Marketing & Advertising',
            'hospitality' => 'Hospitality & Tourism',
            'construction' => 'Construction',
            'logistics' => 'Logistics & Transportation',
            'food' => 'Food & Beverage',
            'agriculture' => 'Agriculture',
            'energy' => 'Energy & Utilities',
            'media' => 'Media & Entertainment',
            'legal' => 'Legal Services',
            'nonprofit' => 'Non-profit',
            'government' => 'Government',
            'other' => 'Other',
        ];
    }

    /**
     * Check if tenant is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->plan === self::PLAN_TRIAL && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if trial has expired
     */
    public function trialExpired(): bool
    {
        if ($this->plan !== self::PLAN_TRIAL || !$this->trial_ends_at) {
            return false;
        }

        return $this->trial_ends_at->isPast();
    }

    /**
     * Check if tenant is in grace period (3 days after trial expiration)
     */
    public function isInGracePeriod(): bool
    {
        if (!$this->trialExpired()) {
            return false;
        }

        return $this->trial_ends_at->copy()->addDays(3)->isFuture();
    }

    /**
     * Check if module is enabled
     */
    public function hasModule(string $module): bool
    {
        $modules = $this->enabled_modules ?? [];
        return in_array($module, $modules);
    }

    /**
     * Enable a module
     */
    public function enableModule(string $module): void
    {
        $modules = $this->enabled_modules ?? [];
        if (!in_array($module, $modules)) {
            $modules[] = $module;
            $this->update(['enabled_modules' => $modules]);
        }
    }

    /**
     * Disable a module
     */
    public function disableModule(string $module): void
    {
        $modules = $this->enabled_modules ?? [];
        $modules = array_filter($modules, fn($m) => $m !== $module);
        $this->update(['enabled_modules' => array_values($modules)]);
    }

    /**
     * Check if tenant subscription is active
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return $this->isOnTrial() || 
               $this->isInGracePeriod() || 
               ($this->subscription_ends_at && $this->subscription_ends_at->isFuture());
    }

    /**
     * Get remaining trial days
     */
    public function getRemainingTrialDays(): ?int
    {
        if (!$this->isOnTrial()) {
            return null;
        }

        return (int) now()->diffInDays($this->trial_ends_at, false);
    }

    /**
     * Get full domain (subdomain.thruoo.com or custom domain)
     */
    public function getFullDomainAttribute(): string
    {
        return $this->domain ?? "{$this->subdomain}." . config('app.tenant_domain', 'thruoo.local');
    }

    /**
     * Get full URL
     */
    public function getUrlAttribute(): string
    {
        $protocol = app()->environment('production') ? 'https' : 'http';
        return "{$protocol}://{$this->full_domain}";
    }

    /**
     * Get the database name for this tenant
     */
    public function getDatabaseName(): string
    {
        return $this->database;
    }

    /**
     * Configure the tenant database connection
     */
    public function getDatabaseConnectionConfig(): array
    {
        $config = config('database.connections.mysql');
        
        return array_merge($config, [
            'database' => $this->database,
        ]);
    }

    /**
     * Activate tenant
     */
    public function activate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Suspend tenant
     */
    public function suspend(): void
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    /**
     * Cancel tenant
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }
}
