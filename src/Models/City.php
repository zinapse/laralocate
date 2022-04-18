<?php

namespace Zinapse\LaraLocate\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laralocate_cities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'code', 'state_id'
    ];

    /**
     * Defining the relationship to states.
     *
     */
    public function state() {
        return $this->belongsTo(State::class);
    }
    
    /**
     * A function that returns this city's country.
     *
     * @return Country
     */
    public function getCountryAttribute(): Country {
        return Country::find($this->state->country_id);
    }
}