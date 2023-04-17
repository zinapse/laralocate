<?php

namespace Zinapse\LaraLocate\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laralocate_countries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'code', 'lat', 'long', 'phone_code', 'currency_name', 'currency_symbol'
    ];
    
    /**
     * Defining the relationship to states.
     *
     */
    public function states() {
        return $this->hasMany(State::class);
    }
}