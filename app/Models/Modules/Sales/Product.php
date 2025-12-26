<?php

namespace App\Models\Modules\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Modules\Module;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'title',
        'title_ar',
        'description',
        'description_ar',
        'sku',
        'barcode',
        'base_price',
        'discount_price',
        'cost_price',
        'base_stock',
        'reserved',
        'min_stock',
        'tax_id',
        'currency_id',
        'module_id',
        'created_by',
        'image',
        'images',
        'status',
        'is_featured',
        'track_stock',
        'slug',
        'meta',
        'settings',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'base_stock' => 'integer',
        'reserved' => 'integer',
        'min_stock' => 'integer',
        'status' => 'boolean',
        'is_featured' => 'boolean',
        'track_stock' => 'boolean',
        'images' => 'array',
        'meta' => 'array',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = ['pivot'];

    /**
     * Get the module this product belongs to
     */
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the user who created this product
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the tax applied to this product
     */
    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get the currency for this product
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get all categories for this product
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category', 'product_id', 'category_id')
            ->withTimestamps();
    }

    /**
     * Get all vendors for this product
     */
    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'product_vendor', 'product_id', 'vendor_id')
            ->withPivot('vendor_price', 'is_primary')
            ->withTimestamps();
    }

    /**
     * Get all variants for this product
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get only active variants
     */
    public function activeVariants()
    {
        return $this->variants()->where('status', true);
    }

    /**
     * Check if product has sufficient stock
     */
    public function hasSufficientStock(int $quantity): bool
    {
        return $this->base_stock >= $quantity;
    }

    /**
     * Reserve stock (reduce available, increase reserved)
     */
    public function reserve(int $quantity): void
    {
        if (!$this->track_stock) {
            return;
        }

        if (!$this->hasSufficientStock($quantity)) {
            throw new \Exception("Insufficient stock for product: {$this->title}");
        }

        $this->base_stock -= $quantity;
        $this->reserved += $quantity;
        $this->save();
    }

    /**
     * Release reserved stock (increase available, decrease reserved)
     */
    public function release(int $quantity): void
    {
        if (!$this->track_stock) {
            return;
        }

        $this->base_stock += $quantity;
        $this->reserved = max(0, $this->reserved - $quantity);
        $this->save();
    }

    /**
     * Deduct reserved stock (finalize sale)
     */
    public function deductReserved(int $quantity): void
    {
        if (!$this->track_stock) {
            return;
        }

        $this->reserved = max(0, $this->reserved - $quantity);
        $this->save();
    }

    /**
     * Check if product is active
     */
    public function isActive(): bool
    {
        return $this->status === true;
    }

    /**
     * Check if stock is low
     */
    public function isLowStock(): bool
    {
        return $this->base_stock <= $this->min_stock;
    }

    /**
     * Get available stock (base_stock - reserved)
     */
    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->base_stock - $this->reserved);
    }

    /**
     * Get effective price (discount or base)
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->discount_price ?? $this->base_price;
    }

    /**
     * Scope: Active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope: Featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('base_stock <= min_stock');
    }

    /**
     * Scope: For specific module
     */
    public function scopeForModule($query, $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }

    /**
     * Scope: In category
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->whereHas('categories', function($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        });
    }

    /**
     * Scope: Search
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('title_ar', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%");
        });
    }
}
