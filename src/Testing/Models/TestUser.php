<?php

namespace ArtflowStudio\Table\Testing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestUser extends Model
{
    use SoftDeletes;

    protected $table = 'af_test_users';

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'phone',
        'role',
        'status',
        'department_id',
        'profile',
        'salary',
        'hire_date',
        'birth_date',
        'bio',
        'avatar_url',
        'is_remote',
        'skills',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'timestamp',
        'profile' => 'json',
        'salary' => 'decimal:2',
        'hire_date' => 'date',
        'birth_date' => 'date',
        'is_remote' => 'boolean',
        'skills' => 'json',
        'last_login_at' => 'timestamp',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the department that owns the user
     */
    public function department()
    {
        return $this->belongsTo(TestDepartment::class, 'department_id');
    }

    /**
     * Get projects managed by this user
     */
    public function managedProjects()
    {
        return $this->hasMany(TestProject::class, 'manager_id');
    }

    /**
     * Get tasks assigned to this user
     */
    public function assignedTasks()
    {
        return $this->hasMany(TestTask::class, 'assigned_to');
    }

    /**
     * Get tasks created by this user
     */
    public function createdTasks()
    {
        return $this->hasMany(TestTask::class, 'created_by');
    }

    public function posts()
    {
        return $this->hasMany(TestPost::class, 'author_id');
    }

    public function comments()
    {
        return $this->hasMany(TestComment::class, 'user_id');
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for remote users
     */
    public function scopeRemote($query)
    {
        return $query->where('is_remote', true);
    }

    /**
     * Scope for users by role
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Get full profile name
     */
    public function getFullProfileNameAttribute()
    {
        return $this->profile['full_name'] ?? $this->name;
    }

    /**
     * Get profile address
     */
    public function getProfileAddressAttribute()
    {
        $address = $this->profile['address'] ?? [];
        return [
            'street' => $address['street'] ?? null,
            'city' => $address['city'] ?? null,
            'state' => $address['state'] ?? null,
            'zip' => $address['zip'] ?? null,
        ];
    }

    /**
     * Get skills as array
     */
    public function getSkillsListAttribute()
    {
        return $this->skills ?? [];
    }

    /**
     * Get formatted salary
     */
    public function getFormattedSalaryAttribute()
    {
        return '$' . number_format($this->salary, 0);
    }

    /**
     * Check if user has specific skill
     */
    public function hasSkill($skill)
    {
        return in_array($skill, $this->skills ?? []);
    }

    /**
     * Get profile value by key with dot notation
     */
    public function getProfileValue($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->profile;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}
