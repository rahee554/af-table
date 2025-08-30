<?php

namespace ArtflowStudio\Table\Testing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestTask extends Model
{
    use SoftDeletes;

    protected $table = 'af_test_tasks';

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'project_id',
        'assigned_to',
        'estimated_hours',
        'actual_hours',
        'completion_percentage',
        'due_date',
        'started_at',
        'completed_at',
        'dependencies',
        'attachments',
        'comments',
        'tags',
        'difficulty_level',
        'category',
        'is_billable',
    ];

    protected $casts = [
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'due_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'dependencies' => 'json',
        'attachments' => 'json',
        'comments' => 'json',
        'tags' => 'json',
        'is_billable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the project that owns the task
     */
    public function project()
    {
        return $this->belongsTo(TestProject::class, 'project_id');
    }

    /**
     * Get the user assigned to the task
     */
    public function assignedUser()
    {
        return $this->belongsTo(TestUser::class, 'assigned_to');
    }

    /**
     * Scope for pending tasks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for in progress tasks
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope for completed tasks
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for overdue tasks
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope for high priority tasks
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'critical']);
    }

    /**
     * Scope for billable tasks
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Check if task is overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Get time remaining until due date
     */
    public function getTimeRemainingAttribute()
    {
        if (!$this->due_date) {
            return null;
        }
        return now()->diffForHumans($this->due_date, true);
    }

    /**
     * Get variance between estimated and actual hours
     */
    public function getHoursVarianceAttribute()
    {
        if (!$this->estimated_hours || !$this->actual_hours) {
            return null;
        }
        return $this->actual_hours - $this->estimated_hours;
    }

    /**
     * Get efficiency percentage
     */
    public function getEfficiencyAttribute()
    {
        if (!$this->estimated_hours || !$this->actual_hours) {
            return null;
        }
        return round(($this->estimated_hours / $this->actual_hours) * 100, 2);
    }

    /**
     * Get tags as array
     */
    public function getTagsListAttribute()
    {
        return $this->tags ?? [];
    }

    /**
     * Get dependencies as array
     */
    public function getDependenciesListAttribute()
    {
        return $this->dependencies ?? [];
    }

    /**
     * Get attachments as array
     */
    public function getAttachmentsListAttribute()
    {
        return $this->attachments ?? [];
    }

    /**
     * Get comments as array
     */
    public function getCommentsListAttribute()
    {
        return $this->comments ?? [];
    }

    /**
     * Check if task has specific tag
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->tags ?? []);
    }

    /**
     * Get duration in working days
     */
    public function getDurationAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        return $this->started_at->diffInWeekdays($this->completed_at);
    }

    /**
     * Calculate billable hours
     */
    public function getBillableHoursAttribute()
    {
        return $this->is_billable ? $this->actual_hours : 0;
    }

    /**
     * Get task age in days
     */
    public function getAgeAttribute()
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get priority weight for sorting
     */
    public function getPriorityWeightAttribute()
    {
        return match($this->priority) {
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0
        };
    }
}
