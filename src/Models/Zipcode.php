<?php

namespace Zinapse\LaraLocate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Zipcode extends Model
{
    public static function ZipcodesNearCity(City $city, int $radius = 5, int $max_rows = 5): array {
        if(empty($city)) return [];

        $lat = $city->lat;
        $long = $city->long;

        $zipcodes = GeoNames::GeoFindNearbyPostalCodes($lat, $long, $radius, $max_rows);

        if(!is_array($zipcodes)) {
            Log::error(__FILE__ . ':' . __LINE__ . ' Error: ' . $zipcodes);
            return [];
        }

        return $zipcodes;
    }
}