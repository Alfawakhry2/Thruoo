<?php

namespace App\Http\Controllers\Modules\Sales\Api;

use App\Http\Controllers\Controller;
use App\Models\Modules\Sales\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UnitController extends Controller
{
    /**
     * List units (paginated)
     */
    public function index(Request $request)
    {
        $units = Unit::with(['category', 'branch', 'creator'])
            ->when($request->category_id, fn ($q) =>
                $q->where('category_id', $request->category_id)
            )
            ->when($request->branch_id, fn ($q) =>
                $q->where('branch_id', $request->branch_id)
            )
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Units fetched successfully',
            'data' => $units,
        ]);
    }

    /**
     * Get all active units (for dropdowns)
     */
    public function all()
    {
        $units = Unit::where('is_active', true)
            ->orderBy('title')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Active units fetched successfully',
            'data' => $units,
        ]);
    }

    /**
     * Show single unit
     */
    public function show($id)
    {
        $unit = Unit::with(['category', 'branch', 'creator'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Unit fetched successfully',
            'data' => $unit,
        ]);
    }

    /**
     * Store new unit (with image & document)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',

            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',

            'property_type' => 'required|in:residential,commercial',
            'listing_type' => 'required|in:renting,selling',
            'unit_type' => 'required|in:apartment,villa,duplex,shale,cabin,office,mall,building',

            'size' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',

            'description' => 'nullable|string',

            'category_id' => 'nullable|exists:categories,id',
            'branch_id' => 'nullable|exists:branches,id',

            'image' => 'nullable|image|max:2048',
            'document' => 'nullable|mimes:pdf,doc,docx|max:10240',
        ]);

        $imagePath = null;
        $documentPath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store('units/images', 'public');
        }

        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')
                ->store('units/documents', 'public');
        }

        $unit = Unit::create([
            ...$data,
            'image_path' => $imagePath,
            'document_path' => $documentPath,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully',
            'data' => $unit->fresh(['category', 'branch', 'creator']),
        ], 201);
    }

    /**
     * Update unit (optionally replace image/document)
     */
    public function update(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'address' => 'nullable|string|max:255',

            'location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',

            'property_type' => 'sometimes|in:residential,commercial',
            'listing_type' => 'sometimes|in:renting,selling',
            'unit_type' => 'sometimes|in:apartment,villa,duplex,shale,cabin,office,mall,building',

            'size' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',

            'description' => 'nullable|string',

            'category_id' => 'nullable|exists:categories,id',
            'branch_id' => 'nullable|exists:branches,id',

            'image' => 'nullable|image|max:2048',
            'document' => 'nullable|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($request->hasFile('image')) {
            if ($unit->image_path) {
                Storage::disk('public')->delete($unit->image_path);
            }

            $data['image_path'] = $request->file('image')
                ->store('units/images', 'public');
        }

        if ($request->hasFile('document')) {
            if ($unit->document_path) {
                Storage::disk('public')->delete($unit->document_path);
            }

            $data['document_path'] = $request->file('document')
                ->store('units/documents', 'public');
        }

        $unit->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Unit updated successfully',
            'data' => $unit->fresh(['category', 'branch', 'creator']),
        ]);
    }

    /**
     * Delete single unit
     */
    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);

        if ($unit->image_path) {
            Storage::disk('public')->delete($unit->image_path);
        }

        if ($unit->document_path) {
            Storage::disk('public')->delete($unit->document_path);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully',
            'data' => null,
        ]);
    }

    /**
     * Batch delete units
     */
    public function batchDelete(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:units,id',
        ]);

        $units = Unit::whereIn('id', $data['ids'])->get();

        foreach ($units as $unit) {
            if ($unit->image_path) {
                Storage::disk('public')->delete($unit->image_path);
            }

            if ($unit->document_path) {
                Storage::disk('public')->delete($unit->document_path);
            }

            $unit->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Units deleted successfully',
            'data' => null,
        ]);
    }

    /**
     * Toggle unit active status
     */
    public function toggleStatus($id)
    {
        $unit = Unit::findOrFail($id);

        $status = $unit->is_active === true ? "Active" : "InActive" ;
        $unit->update([
            'is_active' => ! $unit->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unit status is ' . $status ,
            // 'data' => $unit->fresh(['category', 'branch', 'creator']),
        ]);
    }
}
