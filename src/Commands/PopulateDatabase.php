<?php

namespace Zinapse\LaraLocate\Commands;

use Exception;
use Illuminate\Console\Command;
use Zinapse\LaraLocate\Models\LaraLocate;
use Zinapse\LaraLocate\Models\LaraLocateType;
use ZipArchive;

class PopulateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laralocate:populate
                            {--country=* : Include only this country}';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the LaraLocate tables';

    protected $base_url = 'https://download.geonames.org/export/dump/';
    protected $country_info_file = 'countryInfo.txt';

    protected $object_types = [
        'Country',
        'State',
        'City'
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        LaraLocateType::truncate();

        // Populate the types if not found
        $this->info('Populating location types');

        // Create a record for each type
        foreach($this->object_types as $type) {
            LaraLocateType::insertOrIgnore([
                'name' => $type
            ]);
        }

        // Get the countries
        $countries = $this->parseCountries();

        // Make sure we have data in the countries array
        if(empty($countries)) {
            $this->error('Countries array not populated');
            return;
        }

        // Any countries specified on the command line
        $selected_countries = $this->option('country');

        $cities = [];

        // Iterate through each country
        foreach($countries as $iso => $country) {
            // If we have selected ones then skip the country if it's not in that array
            if(!empty($selected_countries) && !in_array($iso, $selected_countries)) continue;

            // Create the country record
            $type = LaraLocateType::where('name', 'Country')->first();
            $new_country = new LaraLocate([
                'name' => $country,
                'code' => $iso,
                'laralocate_type' => $type->id
            ]);
            $new_country->save();

            // Generate the URL for this countries data file
            $zip_file = $iso . '.zip';
            $zip_url = $this->base_url . $zip_file;

            // Define our new file path
            $filepath = tempnam(sys_get_temp_dir(), $zip_file) ?: $zip_file;

            // Output
            $this->info('Downloading data for: ' . $country);

            // Create the file from the URL
            if(!copy($zip_url, $filepath)) {
                $this->error('Error while downloading file');
                return;
            }

            // Unzip the country's zip file
            $zip_handle = new ZipArchive;
            $res = $zip_handle->open($filepath);
            if($res !== true) {
                $this->error('Unable to unzip data');
                return;
            }
            $zip_handle->extractTo(sys_get_temp_dir());
            $zip_handle->close();

            // Get the new file
            $country_data_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $iso . '.txt';

            // Open our downloaded file
            $file_handle = fopen($country_data_file, 'rb');
            if(empty($file_handle)) {
                $this->error('Unable to create a file handle');
                return;
            }

            $this->info('Parsing country data file...');

            // Iterate line by line
            while(($line = fgets($file_handle)) !== false) {
                // Create an array from the line
                $data = explode("\t", $line);

                /* The main 'geoname' table has the following fields :
                geonameid         : integer id of record in geonames database   
                name              : name of geographical point (utf8) varchar(200)
                asciiname         : name of geographical point in plain ascii characters, varchar(200)
                alternatenames    : alternatenames, comma separated, ascii names automatically transliterated, convenience attribute from alternatename table, varchar(10000)
                latitude          : latitude in decimal degrees (wgs84)
                longitude         : longitude in decimal degrees (wgs84)
                feature class     : see http://www.geonames.org/export/codes.html, char(1)
                feature code      : see http://www.geonames.org/export/codes.html, varchar(10)
                country code      : ISO-3166 2-letter country code, 2 characters
                cc2               : alternate country codes, comma separated, ISO-3166 2-letter country code, 200 characters
                admin1 code       : fipscode (subject to change to iso code), see exceptions below, see file admin1Codes.txt for display names of this code; varchar(20)
                admin2 code       : code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80) 
                admin3 code       : code for third level administrative division, varchar(20)
                admin4 code       : code for fourth level administrative division, varchar(20)
                population        : bigint (8 byte int) 
                elevation         : in meters, integer
                dem               : digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30'' (ca 900mx900m) area in meters, integer. srtm processed by cgiar/ciat.
                timezone          : the iana timezone id (see file timeZone.txt) varchar(40)
                modification date : date of last modification in yyyy-MM-dd format
                 */

                // Set variables based on the index from the above table
                $name = $data[1] ?? null;
                $feature = $data[7] ?? null;
                $data_iso = $data[8] ?? null;
                $cc2 = $data[9] ?? null;
                
                // If we're missing any data then skip this line
                if(empty($name) || empty($feature) || empty($data_iso)) continue;

                // ADM1 is a state
                if(strpos('ADM1', $feature) !== false) {
                    // Get the state type ID
                    $type = LaraLocateType::where('name', 'State')->first();

                    // Make sure we have a cc2 code for this one
                    if(empty($cc2)) continue;

                    // Create the record
                    $new_state = new LaraLocate([
                        'name' => $name,
                        'code' => $cc2,
                        'laralocate_type' => $type->id,
                        'parent_id' => $new_country->id
                    ]);
                    $new_state->save();
                    continue;
                }

                // PPL* (various suffix for PPL exist http://www.geonames.org/export/codes.html)
                // These seem to generally be cities
                if(strpos('PPL', $feature) !== false) {
                    if(empty($cc2)) continue;

                    $cities[] = [
                        'name' => $name,
                        'laralocate_type' => $type,
                        'cc2' => $cc2 ?? ''
                    ];
                }
            }

            // Close the file handle
            fclose($file_handle);

            // Delete the file
            if(!unlink($filepath)) {
                $this->warn('Could not delete file: ' . $filepath);
            }
        }

        $this->info('Parsing cities...');
        foreach($cities as $city) {
            $name = $city['name'];
            $type = $city['laralocate_type'];
            $cc2 = $city['cc2'];

            // Get the parent
            $parent_type = LaraLocateType::where('name', 'State')->first()->id;
            $parent = LaraLocate::where('code', $cc2)->where('laralocate_type', $parent_type)->first();

            // Make sure we have a parent object
            if(empty($parent)) {
                $this->error('No parent found for: ' . $name);
                continue;
            }

            // Create the record
            $this->info('Creating new record for: ' . $name);
            new LaraLocate([
                'name' => $name,
                'laralocate_type' => $type,
                'parent_id' => $parent->id
            ]);
            continue;
        }

        $this->info('Completed');
        return;
    }

    /**
     * Populate an array with all countries specified in this command.
     *
     * @return array
     */
    protected function parseCountries() {
        try {
            // Check if we have the table for laralocate
            $existing = LaraLocate::all();

            // If we have existing data, make sure the user wants to overwrite it
            if(!empty($existing)) {
                if(!$this->confirm('This will overwrite existing laralocate data, would you like to continue?', true)) {
                    $this->warn('Exitting, no data has changed');
                    return null;
                }
                
                // Truncate the table
                LaraLocate::truncate();
            }
        } catch(Exception $e) {}

        // Define an empty countries array
        $countries = [];
        
        // Define our file path
        $filepath = tempnam(sys_get_temp_dir(), $this->country_info_file) ?: $this->country_info_file;
        
        // Output
        $this->info('Downloading country information');

        // Create the file from the URL
        $country_info_url = $this->base_url . $this->country_info_file;
        if(!copy($country_info_url, $filepath)) {
            $this->error('Error while downloading file');
            return null;
        }

        // Open our downloaded file
        $file_handle = fopen($filepath, 'rb');
        if(empty($file_handle)) {
            $this->error('Unable to create a file handle');
            return null;
        }

        // Required index variables
        $iso_index = 0;
        $country_index = 4;

        // Iterate line by line
        while(($line = fgets($file_handle)) !== false) {
            // Skip comments
            if($line[0] == '#') continue;

            // If we're here without a header something went wrong
            // if(empty($iso_index) || empty($country_index)) {
            //     $this->error('Required header values not found');
            //     break;
            // }

            // Split the line by tabs
            $data = explode("\t", $line);

            // Get the data at the index found
            $iso = $data[$iso_index];
            $country = $data[$country_index];

            // Make sure we have data
            if(empty($iso) || empty($country)) continue;

            // Add to the countries array
            $countries[$iso] = $country;
        }

        // Close the file handle
        fclose($file_handle);

        // Delete the file
        if(!unlink($filepath)) {
            $this->warn('Could not delete file: ' . $filepath);
        }

        return $countries;
    }
}