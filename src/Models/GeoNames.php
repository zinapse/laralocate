<?php

namespace Zinapse\LaraLocate\Models;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;

class GeoNames extends Model
{
    public static function GetWebhooks() {
        $webhooks = [
            'search' => ['q'],
            'postalCodeSearch' => ['postalcode'],
            'findNearbyPostalCodes' => ['lat', 'lng'],
            'countrySubdivision' => ['lat', 'lng'],
            'extendedFindNearby' => ['lat', 'lng'],
            'findNearbyPlaceName' => ['lat', 'lng'],
        ];
        
        return $webhooks;
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