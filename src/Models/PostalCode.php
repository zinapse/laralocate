<?php

namespace Zinapse\LaraLocate\Models;

use Illuminate\Database\Eloquent\Model;

class PostalCode extends Model
{
    public static function NearCity(City $city, int $radius = 5, int $max_rows = 5): array|string {
        // Make sure we have a City object
        if(empty($city)) return 'No city provided';

        // Get the City's variables
        $lat = $city->lat;
        $long = $city->long;

        // Get the nearby postal codes
        $zipcodes = GeoNames::GeoFindNearbyPostalCodes($lat, $long, $radius, $max_rows);

        return $zipcodes;
    }
}