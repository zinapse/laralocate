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
        'name', 'code', 'parent_id'
    ];
    
    /**
     * A function that returns this city's country.
     *
     * @return Country
     */
    public function getCountryAttribute(): Country {
        return Country::find($this->state->parent_id);
    }

    /**
     * A function that returns this city's state.
     *
     * @return State
     */
    public function getStateAttribute(): State {
        return State::find($this->parent_id);
    }
}