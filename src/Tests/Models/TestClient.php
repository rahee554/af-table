<?php

namespace ArtflowStudio\Table\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestClient extends Model
{
    protected $table = 'test_clients';

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'address',
        'industry',
        'tier',
        'lifetime_value',
        'first_contract_date',
        'contacts',
        'is_active',
    ];

    protected $casts = [
        'contacts' => 'array',
        'is_active' => 'boolean',
        'lifetime_value' => 'decimal:2',
        'first_contract_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(TestCompany::class, 'company_id');
    }

    public function invoices()
    {
        return $this->hasMany(TestInvoice::class, 'client_id');
    }
}
