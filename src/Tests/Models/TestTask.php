<?php

namespace ArtflowStudio\Table\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestTask extends Model
{
    protected $table = 'test_tasks';

    protected $fillable = [
        'project_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'priority',
        'status',
        'estimated_hours',
        'actual_hours',
        'due_date',
        'completed_at',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'estimated_hours' => 'integer',
        'actual_hours' => 'integer',
        'due_date' => 'date',
        'completed_at' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(TestProject::class, 'project_id');
    }

    public function assignee()
    {
        return $this->belongsTo(TestEmployee::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(TestEmployee::class, 'created_by');
    }

    public function timesheets()
    {
        return $this->hasMany(TestTimesheet::class, 'task_id');
    }
}
