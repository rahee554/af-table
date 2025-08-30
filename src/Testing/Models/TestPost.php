<?php

namespace ArtflowStudio\Table\Testing\Models;

use Illuminate\Database\Eloquent\Model;

class TestPost extends Model
{
    protected $table = 'posts';
    
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'category_id',
        'status',
        'published_at'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'metadata' => 'json'
    ];

    public function user()
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(TestCategory::class, 'category_id');
    }

    public function comments()
    {
        return $this->hasMany(TestComment::class, 'post_id');
    }

    public function tags()
    {
        return $this->belongsToMany(TestTag::class, 'post_tags', 'post_id', 'tag_id');
    }
}
