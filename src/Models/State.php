<?php

namespace Zinapse\LaraLocate\Models;

use Illuminate\Support\Collection;
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
        'name', 'code', 'country_id', 'lat', 'long'
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
     * Static function to help join the tables to find states from a specific country.
     *
     * @param string $country
     * @return Collection
     */
    public static function fromCountry(string $country): Collection {
        $country_id = Country::where('name', $country)->pluck('id')->first();
        return static::where('country_id', $country_id)->get();
    }
}