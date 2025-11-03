<?php

namespace ArtflowStudio\Table\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestTimesheet extends Model
{
    protected $table = 'test_timesheets';

    protected $fillable = [
        'employee_id',
        'task_id',
        'work_date',
        'start_time',
        'end_time',
        'hours',
        'description',
        'is_billable',
        'is_approved',
    ];

    protected $casts = [
        'work_date' => 'date',
        'hours' => 'decimal:2',
        'is_billable' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(TestEmployee::class, 'employee_id');
    }

    public function task()
    {
        return $this->belongsTo(TestTask::class, 'task_id');
    }
}
