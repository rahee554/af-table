<?php

namespace ArtflowStudio\Table\Testing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestProject extends Model
{
    use SoftDeletes;

    protected $table = 'af_test_projects';

    protected $fillable = [
        'name',
        'description',
        'code',
        'status',
        'priority',
        'department_id',
        'manager_id',
        'budget',
        'spent_amount',
        'progress_percentage',
        'start_date',
        'end_date',
        'deadline',
        'requirements',
        'technologies',
        'deliverables',
        'client_name',
        'is_confidential',
        'notes',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'deadline' => 'date',
        'requirements' => 'json',
        'technologies' => 'json',
        'deliverables' => 'json',
        'is_confidential' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the department that owns the project
     */
    public function department()
    {
        return $this->belongsTo(TestDepartment::class, 'department_id');
    }

    /**
     * Get the manager of the project
     */
    public function manager()
    {
        return $this->belongsTo(TestUser::class, 'manager_id');
    }

    /**
     * Get tasks belonging to this project
     */
    public function tasks()
    {
        return $this->hasMany(TestTask::class, 'project_id');
    }

    /**
     * Get active tasks
     */
    public function activeTasks()
    {
        return $this->hasMany(TestTask::class, 'project_id')->whereIn('status', ['pending', 'in_progress', 'review']);
    }

    /**
     * Get completed tasks
     */
    public function completedTasks()
    {
        return $this->hasMany(TestTask::class, 'project_id')->where('status', 'completed');
    }

    /**
     * Scope for active projects
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed projects
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for high priority projects
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'critical']);
    }

    /**
     * Get remaining budget
     */
    public function getRemainingBudgetAttribute()
    {
        return $this->budget - $this->spent_amount;
    }

    /**
     * Get budget utilization percentage
     */
    public function getBudgetUtilizationAttribute()
    {
        if ($this->budget <= 0) {
            return 0;
        }
        return round(($this->spent_amount / $this->budget) * 100, 2);
    }

    /**
     * Check if project is overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->deadline && $this->deadline->isPast() && $this->status !== 'completed';
    }

    /**
     * Get project duration in days
     */
    public function getDurationAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }
        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get technologies as array
     */
    public function getTechnologiesListAttribute()
    {
        return $this->technologies ?? [];
    }

    /**
     * Get requirements as array
     */
    public function getRequirementsListAttribute()
    {
        return $this->requirements ?? [];
    }

    /**
     * Check if project has specific technology
     */
    public function hasTechnology($technology)
    {
        return in_array($technology, $this->technologies ?? []);
    }

    /**
     * Get deliverable status
     */
    public function getDeliverableStatus($deliverable)
    {
        $deliverables = $this->deliverables ?? [];
        return $deliverables[$deliverable]['status'] ?? 'unknown';
    }
}
