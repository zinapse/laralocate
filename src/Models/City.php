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
        'name', 'code', 'state_id', 'lat', 'long'
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

    /**
     * A function that returns postal codes and related data
     * within $radius of this city's latitude and longitude.
     *
     * @param integer $radius The radius to search (in disance unit type defined in the config)
     * @return array
     */
    public function getPostalCodes(int $radius = 5): array {
        return PostalCode::NearCity($this, $radius);
    }
}