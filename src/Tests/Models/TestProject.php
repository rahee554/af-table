<?php

namespace ArtflowStudio\Table\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestProject extends Model
{
    protected $table = 'test_projects';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'budget',
        'spent',
        'start_date',
        'end_date',
        'priority',
        'status',
        'progress',
        'milestones',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'milestones' => 'array',
        'progress' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(TestCompany::class, 'company_id');
    }

    public function employees()
    {
        return $this->belongsToMany(TestEmployee::class, 'test_employee_project', 'project_id', 'employee_id')
            ->withPivot('role', 'hours_allocated', 'joined_at')
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(TestTask::class, 'project_id');
    }

    public function invoices()
    {
        return $this->hasMany(TestInvoice::class, 'project_id');
    }

    public function documents()
    {
        return $this->morphMany(TestDocument::class, 'documentable');
    }
}
