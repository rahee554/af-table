<?php

namespace ArtflowStudio\Table\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A model that hides one of its columns.
 *
 * None of the shipped fixtures declare `$hidden`, so without this the
 * "hidden attributes are never returned" assertion would pass vacuously.
 */
class HiddenColumnModel extends Model
{
    protected $table = 'test_employees';

    protected $hidden = ['phone', 'email'];

    protected $casts = [
        'skills' => 'array',
        'is_active' => 'boolean',
    ];
}
