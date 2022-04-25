<?php

namespace Zinapse\LaraLocate\Models;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;

class GeoNames extends Model
{
    /**
     * Lists all available webhooks. On error returns a string with the error message.
     *
     * @return array|string
     */
    public static function GetWebhooks(): array|string {
        return GeoNames::Webhook(listhooks: true);
    }

    /**
     * Helper to run a Geo search request.
     *
     * @param string $query The string to query for.
     * @param int $max_rows The number of rows to return.
     * @return array|string
     */
    public static function GeoSearch(string $query = '', int $max_rows = 5): array|string {
        return GeoNames::Webhook($query, $max_rows);
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
     * @param integer $lat Latitude
     * @param integer $lng Longitude
     * @param integer $max_rows The number of rows to return.
     * @return array|string
     */
    public static function GeoFindNearbyPostalCodes(int $lat = 0, int $lng = 0, int $max_rows = 5): array|string {
        return GeoNames::Webhook([
            'type' => 'findNearbyPostalCodes',
            'lat' => $lat,
            'lng' => $lng
        ], $max_rows);
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
     * @param string|array $data If $data is a string the search webhook will be used with $data as the search parameter.
     *                           If $data is an array, the 'type' key must be set to a valid webhook.
     *                           @link https://www.geonames.org/export/ws-overview.html
     * @param integer $max_rows  The max number of elements to return.
     * @param bool $listhooks    If this is true, this function will return the webhooks and their required variables to pass with $data.
     * @return string|array      If the return is a string, it's an error string. If the return is an array, it will be
     *                           the results or an empty array.
     */
    public static function Webhook(string|array $data = null, int $max_rows = 5, bool $listhooks = false): string|array {
        // Avaliable webhooks
        $webhooks = [
            'search' => ['q'],
            'postalCodeSearch' => ['postalcode'],
            'findNearbyPostalCodes' => ['lat', 'lng'],
            'countrySubdivision' => ['lat', 'lng'],
            'extendedFindNearby' => ['lat', 'lng'],
            'findNearbyPlaceName' => ['lat', 'lng'],
        ];
        if($listhooks) return $webhooks;

        /**
         * Get the GeoNames username from the config, if one exists.
         * To get a free username, visit the GeoNames login page:
         * @link https://www.geonames.org/login
         */
        $username = config('laralocate.geonames_username');

        // If one isn't found return an error
        if(empty($username)) {
            return 'No GeoNames username provided in laraconfig.php';
        }

        // The base GeoNames URL
        $base_url = 'http://api.geonames.org/';

        // If $data is an array then we're doing more complex requests
        if(is_array($data)) {
            // Get the request type, return an error if none was passed
            $type = $data['type'] ?? null;
            if(empty($type)) return 'Please pass a webhook type in the $data array | webhooks: ' . print_r(array_keys($webhooks));

            // Make sure the type is valid
            $webhook = $webhooks[$type] ?? null;
            if(empty($webhook)) return $type . ' is an invalid webhook';

            
            // Build the request and make sure we have the variables needed
            $request = $base_url . $type . '?';
            foreach($webhook as $hookname => $required) {
                foreach((array)$required as $variable) {
                    $var = $data[$variable] ?? null;
                    if(empty($var)) {
                        return 'Missing required variable: ' . $required;
                    }
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
        } else {
            // Build the request
            $request = $base_url . 'search?q=' . $data . '&maxRows=' . $max_rows . '&username=' . $username;

            // Send the Guzzle HTTP request
            $client = new Client();
            $res = $client->request('GET', $request);

            // Convert the XML to an array and return it
            $xml = simplexml_load_string($res->getBody(), 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_encode($xml);
            $out = json_decode($json, true);
        }

        return $out ?? [];
    }
}