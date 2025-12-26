<?php

namespace App\Http\Controllers\Modules\Sales\Api;

use App\Http\Controllers\Controller;
use App\Models\Modules\Module;
use App\Models\Modules\Sales\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TaxController extends Controller
{
    /**
     * Get all taxes
     */
    public function index($moduleId, Request $request): JsonResponse
    {
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $perPage = $request->query('per_page', 15);
        $status = $request->query('status');

        $query = Tax::with('creator')
            ->where('module_id', $moduleId);

        if ($status && $status !== 'all') {
            $isActive = filter_var($status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        $taxes = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $taxes,
        ]);
    }

    /**
     * Get all taxes (no pagination)
     */
    public function all($moduleId): JsonResponse
    {
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $taxes = Tax::where('module_id', $moduleId)
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $taxes,
        ]);
    }

    /**
     * Create tax
     */
    public function store($moduleId, Request $request): JsonResponse
    {
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $user = Auth::user();
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create taxes',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['module_id'] = $moduleId;
        $data['created_by'] = Auth::id();

        $tax = Tax::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tax created successfully',
            'data' => $tax->load('creator'),
        ], 201);
    }

    /**
     * Get single tax
     */
    public function show($moduleId, $taxId): JsonResponse
    {
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $tax = Tax::with('creator')
            ->where('module_id', $moduleId)
            ->find($taxId);

        if (!$tax) {
            return response()->json([
                'success' => false,
                'message' => 'Tax not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tax,
        ]);
    }

    /**
     * Update tax
     */
    public function update($moduleId, Request $request, $taxId): JsonResponse
    {
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $user = Auth::user();
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update taxes',
            ], 403);
        }

        $tax = Tax::where('module_id', $moduleId)->find($taxId);
        if (!$tax) {
            return response()->json([
                'success' => false,
                'message' => 'Tax not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $tax->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tax updated successfully',
            'data' => $tax->fresh('creator'),
        ]);
    }

    /**
     * Delete tax
     */
    public function destroy($moduleId, $taxId): JsonResponse
    {
        $module = Module::find($moduleId);
        if (!$module) {
            return response()->json(['success' => false, 'message' => 'Module not found'], 404);
        }

        $user = Auth::user();
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete taxes',
            ], 403);
        }

        $tax = Tax::where('module_id', $moduleId)->find($taxId);
        if (!$tax) {
            return response()->json([
                'success' => false,
                'message' => 'Tax not found',
            ], 404);
        }

        // Check if used by products
        if ($tax->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete tax that is used by products',
            ], 400);
        }

        $tax->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tax deleted successfully',
        ]);
    }
}
