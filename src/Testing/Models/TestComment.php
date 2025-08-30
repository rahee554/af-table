<?php

namespace ArtflowStudio\Table\Testing\Models;

use Illuminate\Database\Eloquent\Model;

class TestComment extends Model
{
    protected $table = 'comments';
    
    protected $fillable = [
        'content',
        'post_id',
        'user_id'
    ];

    public function post()
    {
        return $this->belongsTo(TestPost::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }
}
