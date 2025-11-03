<?php

namespace ArtflowStudio\Table\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestEmployee extends Model
{
    use SoftDeletes;

    protected $table = 'test_employees';

    protected $fillable = [
        'company_id',
        'department_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'birth_date',
        'hire_date',
        'salary',
        'employment_type',
        'skills',
        'preferences',
        'position',
        'years_experience',
        'is_active',
    ];

    protected $casts = [
        'skills' => 'array',
        'preferences' => 'array',
        'is_active' => 'boolean',
        'salary' => 'decimal:2',
        'birth_date' => 'date',
        'hire_date' => 'date',
        'years_experience' => 'integer',
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function company()
    {
        return $this->belongsTo(TestCompany::class, 'company_id');
    }

    public function department()
    {
        return $this->belongsTo(TestDepartment::class, 'department_id');
    }

    public function managedDepartment()
    {
        return $this->hasOne(TestDepartment::class, 'manager_id');
    }

    public function projects()
    {
        return $this->belongsToMany(TestProject::class, 'test_employee_project', 'employee_id', 'project_id')
            ->withPivot('role', 'hours_allocated', 'joined_at')
            ->withTimestamps();
    }

    public function assignedTasks()
    {
        return $this->hasMany(TestTask::class, 'assigned_to');
    }

    public function createdTasks()
    {
        return $this->hasMany(TestTask::class, 'created_by');
    }

    public function timesheets()
    {
        return $this->hasMany(TestTimesheet::class, 'employee_id');
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(TestDocument::class, 'uploaded_by');
    }
}
