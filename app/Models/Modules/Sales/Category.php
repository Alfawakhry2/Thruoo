<?php

namespace App\Models\Modules\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Modules\Module;
use App\Models\Modules\Sales\Team;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'description_ar',
        'module_id',
        'created_by',
        'status',
        'order',
        'parent_id', // For subcategories
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'status' => 'string',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = ['pivot'];

    /**
     * Get the module this category belongs to
     */
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the user who created this category
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all teams assigned to this category
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'category_team', 'category_id', 'team_id')
            ->withTimestamps();
    }

    /**
     * Get all products in this category
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_category', 'category_id', 'product_id')
            ->withTimestamps();
    }

    /**
     * Get parent category (for subcategories)
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get all subcategories
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all subcategories recursively
     */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Check if category is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if category has subcategories
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Get all category IDs including subcategories recursively
     */
    public static function getAllCategoryIds($categoryId): array
    {
        $ids = [$categoryId];

        $children = self::where('parent_id', $categoryId)->pluck('id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, self::getAllCategoryIds($childId));
        }

        return array_unique($ids);
    }

    /**
     * Scope: Active categories
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Root categories (no parent)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: For specific module
     */
    public function scopeForModule($query, $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }

    /**
     * Scope: Ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
