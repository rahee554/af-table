<?php

namespace ArtflowStudio\Table\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A model over a table that carries credential-shaped columns.
 *
 * None of the shipped fixtures do, and "we filter out `password`" is not a
 * claim worth making without a table that actually has one. The test creates
 * `test_sensitive` itself; RefreshDatabase drops it again.
 */
class SensitiveColumnModel extends Model
{
    protected $table = 'test_sensitive';

    public $timestamps = false;
}
