<?php

namespace ArtflowStudio\Table\Testing\Models;

use Illuminate\Database\Eloquent\Model;

class TestTag extends Model
{
    protected $table = 'tags';
    
    protected $fillable = [
        'name'
    ];

    public function posts()
    {
        return $this->belongsToMany(TestPost::class, 'post_tags', 'tag_id', 'post_id');
    }
}
