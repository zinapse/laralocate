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
        'name', 'code'
    ];
    
    /**
     * A function that returns all states the country has.
     *
     * @return State
     */
    public function getStatesAttribute(): State {
        return State::where('parent_id', $this->id)->get();
    }
}