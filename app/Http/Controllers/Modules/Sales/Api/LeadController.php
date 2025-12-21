<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Modules\Sales\Lead;
use App\Models\Modules\Sales\LeadSource;
use App\Models\Modules\Sales\LeadStatus;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    /**
     * Get all leads with filters and search
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $search = $request->query('search');
        $statusId = $request->query('status_id');
        $sourceId = $request->query('source_id');
        $assignedTo = $request->query('assigned_to');
        $priority = $request->query('priority');
        $isConverted = $request->query('is_converted');
        $moduleId = $request->query('module_id');

        $user = Auth::user();

        $query = Lead::with(['source', 'status', 'assignedUser', 'creator', 'module']);

        // If not owner/admin, only show leads assigned to user or created by user
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            $query->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }

        // Apply filters
        if ($search) {
            $query->search($search);
        }

        if ($statusId) {
            $query->where('status_id', $statusId);
        }

        if ($sourceId) {
            $query->where('source_id', $sourceId);
        }

        if ($assignedTo) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($isConverted !== null) {
            $query->where('is_converted', filter_var($isConverted, FILTER_VALIDATE_BOOLEAN));
        }

        if ($moduleId) {
            $query->where('module_id', $moduleId);
        }

        $leads = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $leads,
        ]);
    }

    /**
     * Create a new lead
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'needs' => ['nullable', 'string'],
            'lead_value' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'source_id' => ['required', 'exists:lead_sources,id'],
            'status_id' => ['required', 'exists:lead_statuses,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'module_id' => ['nullable', 'exists:modules,id'],
            'notes' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = Auth::id();
        $data['priority'] = $data['priority'] ?? 'medium';

        $lead = Lead::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully',
            'data' => $lead->load(['source', 'status', 'assignedUser', 'creator']),
        ], 201);
    }

    /**
     * Get a specific lead
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();

        $lead = Lead::with(['source', 'status', 'assignedUser', 'creator', 'module'])->find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        // Check permission
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            if ($lead->assigned_to !== $user->id && $lead->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this lead',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $lead,
        ]);
    }

    /**
     * Update a lead
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();

        $lead = Lead::find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        // Check permission
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            if ($lead->assigned_to !== $user->id && $lead->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update this lead',
                ], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'needs' => ['nullable', 'string'],
            'lead_value' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'source_id' => ['sometimes', 'required', 'exists:lead_sources,id'],
            'status_id' => ['sometimes', 'required', 'exists:lead_statuses,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'module_id' => ['nullable', 'exists:modules,id'],
            'notes' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $lead->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully',
            'data' => $lead->fresh(['source', 'status', 'assignedUser', 'creator']),
        ]);
    }

    /**
     * Delete a lead
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();

        // Only owner/admin can delete
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete leads',
            ], 403);
        }

        $lead = Lead::find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        $lead->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully',
        ]);
    }

    /**
     * Assign lead to a user
     */
    public function assign(Request $request, $id): JsonResponse
    {
        $user = Auth::user();

        // Only owner/admin can assign
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to assign leads',
            ], 403);
        }

        $lead = Lead::find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $lead->update(['assigned_to' => $request->assigned_to]);

        return response()->json([
            'success' => true,
            'message' => 'Lead assigned successfully',
            'data' => $lead->fresh(['assignedUser']),
        ]);
    }

    /**
     * Convert lead (mark as won/converted)
     */
    public function convert($id): JsonResponse
    {
        $user = Auth::user();

        $lead = Lead::find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        // Check permission
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            if ($lead->assigned_to !== $user->id && $lead->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to convert this lead',
                ], 403);
            }
        }

        if ($lead->is_converted) {
            return response()->json([
                'success' => false,
                'message' => 'Lead is already converted',
            ], 400);
        }

        $lead->markAsConverted();

        return response()->json([
            'success' => true,
            'message' => 'Lead converted successfully',
            'data' => $lead->fresh(),
        ]);
    }

    /**
     * Batch delete leads
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Only owner/admin can batch delete
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete leads',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:leads,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        Lead::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leads deleted successfully',
        ]);
    }

    /**
     * Batch assign leads
     */
    public function batchAssign(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Only owner/admin can batch assign
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to assign leads',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['required', 'integer', 'exists:leads,id'],
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        Lead::whereIn('id', $request->lead_ids)
            ->update(['assigned_to' => $request->assigned_to]);

        return response()->json([
            'success' => true,
            'message' => 'Leads assigned successfully',
        ]);
    }

    /**
     * Get lead statistics
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();

        $query = Lead::query();

        // If not owner/admin, only show stats for user's leads
        if (!$user->isOwner() && !$user->hasRole('Super Admin')) {
            $query->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }

        $totalLeads = $query->count();
        $convertedLeads = (clone $query)->where('is_converted', true)->count();
        $totalValue = (clone $query)->sum('lead_value');
        $averageValue = $totalLeads > 0 ? $totalValue / $totalLeads : 0;

        // Leads by status
        $byStatus = (clone $query)
            ->select('status_id', DB::raw('count(*) as count'))
            ->with('status:id,name,name_ar,color')
            ->groupBy('status_id')
            ->get()
            ->map(function($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                ];
            });

        // Leads by source
        $bySource = (clone $query)
            ->select('source_id', DB::raw('count(*) as count'))
            ->with('source:id,name,name_ar')
            ->groupBy('source_id')
            ->get()
            ->map(function($item) {
                return [
                    'source' => $item->source,
                    'count' => $item->count,
                ];
            });

        // Leads by priority
        $byPriority = (clone $query)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get();

        // Recent leads (last 7 days)
        $recentLeads = (clone $query)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_leads' => $totalLeads,
                'converted_leads' => $convertedLeads,
                'conversion_rate' => $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0,
                'total_value' => round($totalValue, 2),
                'average_value' => round($averageValue, 2),
                'recent_leads' => $recentLeads,
                'by_status' => $byStatus,
                'by_source' => $bySource,
                'by_priority' => $byPriority,
            ],
        ]);
    }
}
