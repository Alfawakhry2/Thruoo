<?php

namespace App\Http\Controllers\Modules\Sales\Api;

use App\Http\Controllers\Controller;
use App\Models\Modules\Module;
use App\Models\Modules\Sales\Product;
use App\Models\Modules\Sales\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Get all products
     */
    public function index($moduleId, Request $request): JsonResponse
    {
        // Verify module
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $perPage = $request->query('per_page', 15);
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $status = $request->query('status');
        $isFeatured = $request->query('is_featured');
        $lowStock = $request->query('low_stock');

        $query = Product::with(['categories:id,name,name_ar', 'tax', 'currency'])
            ->where('module_id', $moduleId);

        // Search
        if ($search) {
            $query->search($search);
        }

        // Filter by category (including subcategories)
        if ($categoryId) {
            $categoryIds = Category::getAllCategoryIds($categoryId);
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        // Filter by status
        if ($status !== null) {
            $query->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter featured
        if ($isFeatured !== null) {
            $query->where('is_featured', filter_var($isFeatured, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter low stock
        if ($lowStock) {
            $query->lowStock();
        }

        $products = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Create product
     */
    public function store($moduleId, Request $request): JsonResponse
    {
        // Verify module
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:base_price'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'base_stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'tax_id' => ['nullable', 'exists:taxes,id'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'image' => ['nullable', 'image', 'max:2048'],
            'images.*' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'track_stock' => ['nullable', 'boolean'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['exists:categories,id'],
            'vendor_ids' => ['nullable', 'array'],
            'vendor_ids.*' => ['exists:vendors,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['nullable', 'string'],
            'variants.*.name_ar' => ['nullable', 'string'],
            'variants.*.sku' => ['nullable', 'string', 'unique:product_variants,sku'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'max:2048'],
            'variants.*.status' => ['nullable', 'boolean'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.attribute_value_ids.*' => ['exists:attribute_values,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();

            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = 'PRD-' . strtoupper(Str::random(8));
            }

            // Generate slug
            $data['slug'] = Str::slug($data['title']) . '-' . time();

            $data['module_id'] = $moduleId;
            $data['created_by'] = Auth::id();

            // Handle main image
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('products', 'public');
            }

            // Handle additional images
            if ($request->hasFile('images')) {
                $images = [];
                foreach ($request->file('images') as $image) {
                    $images[] = $image->store('products', 'public');
                }
                $data['images'] = $images;
            }

            // Extract related data
            $categoryIds = $data['category_ids'] ?? [];
            $vendorIds = $data['vendor_ids'] ?? [];
            $variantsData = $data['variants'] ?? [];

            unset($data['category_ids'], $data['vendor_ids'], $data['variants']);

            // Create product
            $product = Product::create($data);

            // Attach categories
            $product->categories()->sync($categoryIds);

            // Attach vendors
            if (!empty($vendorIds)) {
                $product->vendors()->sync($vendorIds);
            }

            // Create variants
            foreach ($variantsData as $variantData) {
                $attributeValueIds = $variantData['attribute_value_ids'] ?? [];
                unset($variantData['attribute_value_ids']);

                // Handle variant image
                if (isset($variantData['image']) && $variantData['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $variantData['image'] = $variantData['image']->store('variants', 'public');
                }

                $variant = $product->variants()->create($variantData);

                // Attach attribute values
                if (!empty($attributeValueIds)) {
                    $variant->attributeValues()->sync($attributeValueIds);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->load(['categories', 'vendors', 'variants.attributeValues']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single product
     */
    public function show($moduleId, $productId, Request $request): JsonResponse
    {
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $isMobile = $request->query('platform') === 'mobile';

        $query = Product::with([
            'categories',
            'vendors',
            'tax',
            'currency',
            'variants' => function ($q) use ($isMobile) {
                if ($isMobile) {
                    $q->where('status', true);
                }
                $q->with('attributeValues.attribute');
            },
        ])->where('module_id', $moduleId);

        if ($isMobile) {
            $query->where('status', true);
        }

        $product = $query->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update product
     */
    public function update($moduleId, Request $request, $productId): JsonResponse
    {
        // Verify module
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $user = Auth::user();
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update products',
            ], 403);
        }

        $product = Product::where('module_id', $moduleId)->find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'title_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,' . $productId],
            'barcode' => ['nullable', 'string', 'max:100'],
            'base_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:base_price'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'base_stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'tax_id' => ['nullable', 'exists:taxes,id'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'image' => ['nullable', 'image', 'max:2048'],
            'images.*' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'track_stock' => ['nullable', 'boolean'],
            'category_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'category_ids.*' => ['exists:categories,id'],
            'vendor_ids' => ['nullable', 'array'],
            'vendor_ids.*' => ['exists:vendors,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'exists:product_variants,id'],
            'variants.*.name' => ['nullable', 'string'],
            'variants.*.name_ar' => ['nullable', 'string'],
            'variants.*.sku' => ['nullable', 'string', 'unique:product_variants,sku'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'max:2048'],
            'variants.*.status' => ['nullable', 'boolean'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.attribute_value_ids.*' => ['exists:attribute_values,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();

            // Handle main image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $data['image'] = $request->file('image')->store('products', 'public');
            }

            // Handle additional images
            if ($request->hasFile('images')) {
                // Delete old images
                if ($product->images) {
                    foreach ($product->images as $oldImage) {
                        if (Storage::disk('public')->exists($oldImage)) {
                            Storage::disk('public')->delete($oldImage);
                        }
                    }
                }

                $images = [];
                foreach ($request->file('images') as $image) {
                    $images[] = $image->store('products', 'public');
                }
                $data['images'] = $images;
            }

            // Extract related data
            $categoryIds = $data['category_ids'] ?? null;
            $vendorIds = $data['vendor_ids'] ?? null;
            $variantsData = $data['variants'] ?? [];

            unset($data['category_ids'], $data['vendor_ids'], $data['variants']);

            // Update product
            $product->update($data);

            // Sync categories
            if ($categoryIds !== null) {
                $product->categories()->sync($categoryIds);
            }

            // Sync vendors
            if ($vendorIds !== null) {
                $product->vendors()->sync($vendorIds);
            }

            // Handle variants
            $existingVariantIds = [];

            foreach ($variantsData as $variantData) {
                $attributeValueIds = $variantData['attribute_value_ids'] ?? [];
                unset($variantData['attribute_value_ids']);

                // Handle variant image
                if (isset($variantData['image']) && $variantData['image'] instanceof \Illuminate\Http\UploadedFile) {
                    // Delete old variant image if updating
                    if (isset($variantData['id'])) {
                        $existingVariant = $product->variants()->find($variantData['id']);
                        if ($existingVariant && $existingVariant->image && Storage::disk('public')->exists($existingVariant->image)) {
                            Storage::disk('public')->delete($existingVariant->image);
                        }
                    }
                    $variantData['image'] = $variantData['image']->store('variants', 'public');
                }

                if (isset($variantData['id'])) {
                    // Update existing variant
                    $variant = $product->variants()->find($variantData['id']);
                    if ($variant) {
                        $variant->update($variantData);
                        $variant->attributeValues()->sync($attributeValueIds);
                        $existingVariantIds[] = $variant->id;
                    }
                } else {
                    // Create new variant
                    $newVariant = $product->variants()->create($variantData);
                    $newVariant->attributeValues()->sync($attributeValueIds);
                    $existingVariantIds[] = $newVariant->id;
                }
            }

            // Delete removed variants
            $removedVariants = $product->variants()->whereNotIn('id', $existingVariantIds)->get();
            foreach ($removedVariants as $removedVariant) {
                // Delete variant image
                if ($removedVariant->image && Storage::disk('public')->exists($removedVariant->image)) {
                    Storage::disk('public')->delete($removedVariant->image);
                }
                $removedVariant->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->fresh(['categories', 'vendors', 'variants.attributeValues.attribute', 'tax', 'currency']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle product status
     */
    public function toggleStatus($moduleId, $productId): JsonResponse
    {
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $user = Auth::user();
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update products',
            ], 403);
        }

        $product = Product::where('module_id', $moduleId)->find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $product->update(['status' => !$product->status]);

        return response()->json([
            'success' => true,
            'message' => 'Product status updated successfully',
            'data' => $product,
        ]);
    }

    /**
     * Batch delete products
     */
    public function batchDelete($moduleId, Request $request): JsonResponse
    {
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $user = Auth::user();
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete products',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:products,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $products = Product::where('module_id', $moduleId)
                ->whereIn('id', $request->ids)
                ->get();

            foreach ($products as $product) {
                // Delete images
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                if ($product->images) {
                    foreach ($product->images as $image) {
                        if (Storage::disk('public')->exists($image)) {
                            Storage::disk('public')->delete($image);
                        }
                    }
                }

                // Delete variant images
                foreach ($product->variants as $variant) {
                    if ($variant->image && Storage::disk('public')->exists($variant->image)) {
                        Storage::disk('public')->delete($variant->image);
                    }
                }

                $product->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Products deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete products: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete product
     */
    public function destroy($moduleId, $productId): JsonResponse
    {
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $user = Auth::user();
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete products',
            ], 403);
        }

        $product = Product::where('module_id', $moduleId)->find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Delete images
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            if ($product->images) {
                foreach ($product->images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }

            // Delete variant images
            foreach ($product->variants as $variant) {
                if ($variant->image) {
                    Storage::disk('public')->delete($variant->image);
                }
            }

            $product->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product: ' . $e->getMessage(),
            ], 500);
        }
    }
}
