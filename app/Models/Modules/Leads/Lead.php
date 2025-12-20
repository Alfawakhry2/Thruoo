<?php

namespace App\Models\Modules\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The connection name for the model.
     */
    protected $connection = 'tenant';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'position',
        'city',
        'state',
        'country',
        'address',
        'zip_code',
        'website',
        'needs',
        'lead_value',
        'priority',
        'source_id',
        'status_id',
        'assigned_to',
        'created_by',
        'module_id',
        'notes',
        'custom_fields',
        'last_contacted_at',
        'converted_at',
        'is_converted',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'lead_value' => 'decimal:2',
            'custom_fields' => 'array',
            'last_contacted_at' => 'datetime',
            'converted_at' => 'datetime',
            'is_converted' => 'boolean',
        ];
    }

    /**
     * Get the source of the lead
     */
    public function source()
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    /**
     * Get the status of the lead
     */
    public function status()
    {
        return $this->belongsTo(LeadStatus::class, 'status_id');
    }

    /**
     * Get the user this lead is assigned to
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this lead
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the module this lead belongs to
     */
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    /**
     * Scope to filter by source
     */
    public function scopeBySource($query, $sourceId)
    {
        return $query->where('source_id', $sourceId);
    }

    /**
     * Scope to filter by assigned user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope to filter by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get converted leads
     */
    public function scopeConverted($query)
    {
        return $query->where('is_converted', true);
    }

    /**
     * Scope to get unconverted leads
     */
    public function scopeUnconverted($query)
    {
        return $query->where('is_converted', false);
    }

    /**
     * Scope to search leads
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('company', 'like', "%{$search}%");
        });
    }

    /**
     * Mark lead as converted
     */
    public function markAsConverted()
    {
        $this->update([
            'is_converted' => true,
            'converted_at' => now(),
        ]);
    }

    /**
     * Update last contacted timestamp
     */
    public function markAsContacted()
    {
        $this->update(['last_contacted_at' => now()]);
    }
}
