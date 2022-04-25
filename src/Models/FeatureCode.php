<?php

namespace Zinapse\LaraLocate\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureCode extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laralocate_feature_codes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code', 'short_desc', 'long_desc'
    ];
}