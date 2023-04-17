<?php

namespace Zinapse\LaraLocate\Models;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;

class GeoNames extends Model
{
    /**
     * Lists all available webhooks.
     *
     * @return array
     */
    public static function GetWebhooks(): array|string {
        // Avaliable webhooks
        $webhooks = [
            'search' => ['q'],
            'postalCodeSearch' => ['postalcode'],
            'findNearbyPostalCodes' => ['lat', 'lng', 'radius'],
            'countrySubdivision' => ['lat', 'lng'],
            'extendedFindNearby' => ['lat', 'lng'],
            'findNearbyPlaceName' => ['lat', 'lng'],
        ];
        return $webhooks;
    }

    /**
     * Helper to run a Geo search request.
     *
     * @param string $query The string to query for.
     * @param int $max_rows The number of rows to return.
     * @return array|string
     */
    public static function GeoSearch(string $query = '', int $max_rows = 5): array|string {
        return GeoNames::Webhook([
            'type' => 'search',
            'q' => $query
        ], $max_rows);
    }

    /**
     * Helper to run a GeoNames postalCodeSearch request.
     *
     * @param string $postalcode The postal code to search for.
     * @param int $max_rows The number of rows to return.
     * @return array|string
     */
    public static function GeoPostalCodeSearch(string $postalcode = '', int $max_rows = 5): array|string {
        return GeoNames::Webhook([
            'type' => 'postalCodeSearch',
            'postalcode' => $postalcode
        ], $max_rows);
    }

    /**
     * Helper to run a GeoNames findNearbyPostalCodes request.
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param integer $radius The radius to search
     * @param integer $max_rows The number of rows to return.
     * @return array|string
     */
    public static function GeoFindNearbyPostalCodes(float $lat = 0, float $lng = 0, int $radius = 5, int $max_rows = 5): array|string {
        $response = GeoNames::Webhook([
            'type' => 'findNearbyPostalCodes',
            'lat' => $lat,
            'lng' => $lng,
            'radius' => $radius,
        ], $max_rows);

        return $response['code'] ?? 'Invalid response in GeoFindNearbyPostalCodes';
    }

    /**
     * Helper to run a GeoNames countrySubdivision request.
     *
     * @param integer $lat Latitude
     * @param integer $lng Longitude
     * @param integer $max_rows The number of rows to return.
     * @return array|string
     */
    public static function GeoCountrySubdivision(int $lat = 0, int $lng = 0, int $max_rows = 5): array|string {
        return GeoNames::Webhook([
            'type' => 'countrySubdivision',
            'lat' => $lat,
            'lng' => $lng
        ], $max_rows);
    }

    /**
     * Helper to run a GeoNames extendedFindNearby request.
     *
     * @param integer $lat Latitude
     * @param integer $lng Longitude
     * @param integer $max_rows The number of rows to return.
     * @return array|string
     */
    public static function GeoExtendedFindNearby(int $lat = 0, int $lng = 0, int $max_rows = 5): array|string {
        return GeoNames::Webhook([
            'type' => 'extendedFindNearby',
            'lat' => $lat,
            'lng' => $lng
        ], $max_rows);
    }

    /**
     * Helper to run a GeoNames findNearbyPlaceName request.
     *
     * @param integer $lat Longitude
     * @param integer $lng Longitude
     * @param integer $max_rows The number of rows to return.
     * @return array|string
     */
    public static function GeoFindNearbyPlaceName(int $lat = 0, int $lng = 0, int $max_rows = 5): array|string {
        return GeoNames::Webhook([
            'type' => 'findNearbyPlaceName',
            'lat' => $lat,
            'lng' => $lng
        ], $max_rows);
    }

    /**
     * Run a GeoNames webhook.
     *
     * @param array $data        An array with data passed to the request. 
     *                           Format: ['type' => 'WEBHOOK', 'PARAM' => 'VALUE']
     *                           Example: ['type' => 'search', 'q' => 'New York']
     *                           You can pass as many parameters in the array as you like.
     *                           @link https://www.geonames.org/export/ws-overview.html
     * @param integer $max_rows  The max number of elements to return.
     * @param bool $listhooks    If this is true, this function will return the webhooks and their required variables to pass with $data.
     * @return string|array      If the return is a string, it's an error message. If the return is an array, it will be
     *                           the results or an empty array.
     */
    public static function Webhook(array $data = null, int $max_rows = 5): string|array {
        // Get the webhooks
        $webhooks = GeoNames::GetWebhooks();

        /**
         * Get the GeoNames username from the config, if one exists.
         * To get a free username, visit the GeoNames login page:
         * @link https://www.geonames.org/login
         */
        $username = config('laralocate.geonames_username');

        // If one isn't found return an error
        if(empty($username)) return 'No GeoNames username provided in laraconfig.php';

        // The base GeoNames URL
        $base_url = 'http://api.geonames.org/';
            
        // Get the request type, return an error if none was passed
        $type = $data['type'] ?? null;
        if(empty($type)) return 'Please pass a webhook type in the $data array | webhooks: ' . print_r(array_keys($webhooks));

        // Make sure the type is valid
        $webhook = $webhooks[$type] ?? null;
        if(empty($webhook)) return $type . ' is an invalid webhook';
        
        // Build the request and make sure we have the variables needed
        $request = $base_url . $type . '?';
        foreach($webhook as $required) {
            foreach((array)$required as $key) {
                $var = $data[$key] ?? null;

                // If the variable is empty
                if(empty($var)) return 'Missing required variable: ' . $required;

                // Format miles to kilometers if specified
                if($key == 'radius' && !empty($var)) {
                    if(config('laralocate.distance_unit_type') === 'mi') {
                        $var = (float)$var * 1.609344;
                    }
                }

                // Append the request URI
                $request .= $required . '=' . $var . '&';
            }
        }

        // Append the max rows and username to the request
        $request .= 'maxRows=' . $max_rows . '&username=' . $username;

        // Send the Guzzle HTTP request
        $client = new Client();
        $res = $client->request('GET', $request);

        // Convert the XML to an array and return it
        $xml = simplexml_load_string($res->getBody(), 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);
        $out = json_decode($json, true);

        return $out;
    }
}