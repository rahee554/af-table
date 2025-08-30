<?php

namespace ArtflowStudio\Table\Testing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestDepartment extends Model
{
    use SoftDeletes;

    protected $table = 'af_test_departments';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'metadata',
        'budget',
        'employee_count',
        'established_date',
    ];

    protected $casts = [
        'metadata' => 'json',
        'budget' => 'decimal:2',
        'established_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get users belonging to this department
     */
    public function users()
    {
        return $this->hasMany(TestUser::class, 'department_id');
    }

    /**
     * Get projects belonging to this department
     */
    public function projects()
    {
        return $this->hasMany(TestProject::class, 'department_id');
    }

    /**
     * Get active users in this department
     */
    public function activeUsers()
    {
        return $this->hasMany(TestUser::class, 'department_id')->where('status', 'active');
    }

    /**
     * Scope for active departments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive departments
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Get formatted budget
     */
    public function getFormattedBudgetAttribute()
    {
        return '$' . number_format($this->budget, 2);
    }

    /**
     * Get metadata value by key
     */
    public function getMetadataValue($key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
}
