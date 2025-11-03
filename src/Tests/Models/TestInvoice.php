<?php

namespace ArtflowStudio\Table\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestInvoice extends Model
{
    protected $table = 'test_invoices';

    protected $fillable = [
        'client_id',
        'project_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'amount',
        'tax',
        'total',
        'status',
        'notes',
        'line_items',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'line_items' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(TestClient::class, 'client_id');
    }

    public function project()
    {
        return $this->belongsTo(TestProject::class, 'project_id');
    }
}
