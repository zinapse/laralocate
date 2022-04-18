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
     * Defining relationships to cities.
     *
     */
    public function cities() {
        return $this->hasMany(City::class);
    }

    /**
     * Defining relationships to countries.
     *
     */
    public function country() {
        return $this->belongsTo(Country::class);
    }

    /**
     * Scope function to help join the tables to find states from a specific country.
     *
     * @param Builder $query
     * @param string $country
     */
    public static function fromCountry($country) {
        $country_id = Country::where('name', $country)->pluck('id')->first();
        return static::where('country_id', $country_id)->get();
    }
}