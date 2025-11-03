<?php

namespace ArtflowStudio\Table\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestCompany extends Model
{
    use SoftDeletes;

    protected $table = 'test_companies';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'country',
        'city',
        'employee_count',
        'revenue',
        'founded_date',
        'metadata',
        'status',
        'is_verified',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_verified' => 'boolean',
        'revenue' => 'decimal:2',
        'founded_date' => 'date',
    ];

    public function departments()
    {
        return $this->hasMany(TestDepartment::class, 'company_id');
    }

    public function employees()
    {
        return $this->hasMany(TestEmployee::class, 'company_id');
    }

    public function projects()
    {
        return $this->hasMany(TestProject::class, 'company_id');
    }

    public function clients()
    {
        return $this->hasMany(TestClient::class, 'company_id');
    }

    public function documents()
    {
        return $this->morphMany(TestDocument::class, 'documentable');
    }
}
