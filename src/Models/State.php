<?php

namespace Zinapse\LaraLocate\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laralocate_states';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'code', 'parent_id'
    ];

    /**
     * A function that returns the country for this state.
     *
     * @return Country
     */
    public function getCountryAttribute(): Country {
        return Country::find($this->parent_id);
    }
    
    /**
     * A function that returns all cities this state has.
     *
     * @return City
     */
    public function getCitiesAttribute(): City {
        return City::where('parent_id', $this->id)->get();
    }
}