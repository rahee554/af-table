<?php

namespace ArtflowStudio\Table\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestDepartment extends Model
{
    protected $table = 'test_departments';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'budget',
        'manager_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'budget' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(TestCompany::class, 'company_id');
    }

    public function manager()
    {
        return $this->belongsTo(TestEmployee::class, 'manager_id');
    }

    public function employees()
    {
        return $this->hasMany(TestEmployee::class, 'department_id');
    }
}
