<?php

namespace Zinapse\LaraLocate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class LaraLocate extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laralocate';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'laralocate_type', 'name', 'data'
    ];
    
    public function scopeCountry($query, $type) {
        $country_type = LaraLocateType::where('name', 'Country')->first()->id;
        if(empty($country_type)) {
            Log::warning('No country type found in LaraLocateType');
            return;
        }

        return $query->where('laralocate_type', $country_type)->where('name', $type);
    }

    public static function getCities($state) {

    }

    /**
     * A function that returns all states the $country has.
     *
     * @param string $country
     * @return array
     */
    public static function getStates($country) {
        // Return an error if no country is defined
        if(empty($country)) return ['error' => 'No country passed'];

        // An array holding our return
        $out = [];
        
        // Get all the records
        $all = LaraLocate::all();

        // Get this country's record
        $country_object = LaraLocate::country($country)->first();

        // Iterate through all records
        foreach($all as $record) {
            // If the parent_id is this country, then this is a state, so add it to $out
            if($record->parent_id == $country_object->id) $out[] = $record;
        }

        return $out;
    }
}