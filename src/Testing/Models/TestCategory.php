<?php

namespace ArtflowStudio\Table\Testing\Models;

use Illuminate\Database\Eloquent\Model;

class TestCategory extends Model
{
    protected $table = 'categories';
    
    protected $fillable = [
        'name',
        'description'
    ];

    public function posts()
    {
        return $this->hasMany(TestPost::class, 'category_id');
    }
}
