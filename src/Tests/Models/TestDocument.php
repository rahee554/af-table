<?php

namespace ArtflowStudio\Table\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestDocument extends Model
{
    protected $table = 'test_documents';

    protected $fillable = [
        'documentable_id',
        'documentable_type',
        'title',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'description',
        'metadata',
        'uploaded_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(TestEmployee::class, 'uploaded_by');
    }
}
