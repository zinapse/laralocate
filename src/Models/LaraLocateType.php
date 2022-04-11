<?php

namespace Zinapse\LaraLocate\Models;

use Illuminate\Database\Eloquent\Model;

class LaraLocateType extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laralocate_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    public $timestamps = false;
}