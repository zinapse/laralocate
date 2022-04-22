<?php

namespace Zinapse\LaraLocate\Models;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;

class GeoNames extends Model
{
    /**
     * Run a GeoNames webhook.
     *
     * @param string|array $data If $data is a string the search webhook will be used with $data as the search parameter.
     *                           If $data is an array, the 'type' key must be set to a valid webhook.
     *                           @link https://www.geonames.org/export/ws-overview.html
     * @param integer $max_rows  The max number of elements to return.
     * @return string|array      If the return is a string, it's an error string. If the return is an array, it will be
     *                           the results or an empty array.
     */
    public static function geoSearch(string|array $data, int $max_rows = 5): string|array {
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
        $base_url = 'http://api.geonames.org/'; //findNearbyPostalCodes?lat=40&lng=-82&username=zinapse';

        // If $data is an array then we're doing more complex requests
        if(is_array($data)) {
            // Avaliable webhooks
            $webhooks = [
                'findNearbyPostalCodes' => ['lat', 'lng'],
                'countrySubdivision' => ['lat', 'lng'],
                'postalCodeSearch' => ['postalcode'],
                'search' => ['q']
            ];
            
            // Get the request type, return an error if none was passed
            $type = $data['type'] ?? null;
            if(empty($type)) return 'Please pass a webhook type in the $data array | webhooks: ' . print_r(array_keys($webhooks));

            // Make sure the type is valid
            $webhook = $webhooks[$type] ?? null;
            if(empty($webhook)) return $type . ' is an invalid webhook';

            
            // Build the request and make sure we have the variables needed
            $request = $base_url . $type . '?';
            foreach($webhook as $required) {
                $var = $data[$required] ?? null;
                if(empty($var)) {
                    return 'Missing required variable: ' . $required;
                }
                $request .= $var . '=' . $var . '&';
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
            $request = $base_url . 'search?q=' . $data . '&';

            // Append the username to the request
            $request .= 'maxRows=' . $max_rows . '&username=' . $username;

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