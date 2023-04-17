<?php

namespace Zinapse\LaraLocate\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Zinapse\LaraLocate\Models\City;
use Zinapse\LaraLocate\Models\State;
use Zinapse\LaraLocate\Models\Country;

class PopulateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laralocate:populate
                            {--c|cities : Be more verbose when adding cities}
                            {--f|file= : Path to a file to use instead of downloading one}';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the LaraLocate tables';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        // Check if we already have data
        $count_country = Country::count();
        $count_state = State::count();
        $count_city = City::count();
        if(($count_country > 0 || $count_state > 0 || $count_city > 0) && 
            !$this->confirm('LaraLocate data already exists, would you like to continue and overwrite the existing data?', true)) return;

        // Check if we should be verbose with cities
        $verbose = !empty($this->option('cities'));

        // Truncate the existing data
        $this->truncateTables();

        // The file set as an option
        $option_file = $this->option('file') ?? '';

        // If we have an option
        if(!empty($option_file) && file_exists($option_file)) {
            // If we have a file to use
            $filepath = $this->option('file');
        } else {
            // If the file was passed but not found
            if(!empty($option_file)) {
                $this->warn('File "' . $option_file . '" not found, falling back to download URL');
            }

            // Output
            $this->info('Downloading JSON file...');
    
            // Download the JSON file required
            $file_url = config('laralocate.file_url');
            $filepath = tempnam(sys_get_temp_dir(), 'world_info.json') ?: 'world_info.json';
            
            // cURL and file variables
            $ch = curl_init();
            $fp = fopen($filepath, 'w');

            // Setting cURL options
            curl_setopt($ch, CURLOPT_URL, $file_url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            
            // Get the data
            $data = curl_exec($ch);

            if(curl_error($ch)) {
                // Log any errors
                Log::error(curl_error($ch));
            } else {
                // Write to the file
                fwrite($fp, $data);
            }

            // Free the cURL object
            curl_close($ch);

            // if(!copy($file_url, $filepath)) {
            //     $this->error('Unable to download world data file from: ' . $file_url);
            //     return;
            // }
        }

        // Output
        $this->info('Parsing JSON');

        // JSON to an array
        $json = json_decode(file_get_contents($filepath));

        // If there's no data
        if(empty($json)) {
            $this->error('No JSON data found');
            return;
        }

        // Parse the array
        foreach($json as $country) {
            // Add the country record
            $new_country = new Country;
            $new_country->name = $country->name;
            $new_country->code = $country->iso2;
            $new_country->lat = $country->latitude;
            $new_country->long = $country->longitude;
            $new_country->phone_code = $country->phone_code;
            $new_country->currency_name = $country->currency_name;
            $new_country->currency_symbol = $country->currency_symbol;
            $new_country->save();
            $this->line('<fg=yellow>Added country: ' . $country->name . '</>');

            // Skip countries without states
            if(empty($country->states)) continue;
            
            // Iterate through the states
            $states = $country->states;
            foreach($states as $state) {
                // Add the state record
                $new_state = new State;
                $new_state->name = $state->name;
                $new_state->code = $state->state_code;
                $new_state->country_id = $new_country->id;
                $new_state->lat = $state->latitude;
                $new_state->long = $state->longitude;
                $new_state->save();
                $this->line('<fg=cyan>Added state: ' . $state->name . '</>');

                // Iterate through the cities
                if(!$verbose) $this->line('<fg=blue>Adding cities...</>');
                $cities = $state->cities;
                foreach($cities as $city) {
                    $new_city = new City;
                    $new_city->name = $city->name;
                    $new_city->state_id = $new_state->id;
                    $new_city->lat = $city->longitude;
                    $new_city->long = $city->longitude;
                    $new_city->save();
                    if($verbose) $this->line('<fg=blue>Added city: ' . $city->name . '</>');
                }
            }
        }

        // Delete the JSON file, or warn if it isn't deleted
        if(!unlink($filepath)) {
            $this->warn('Failed to delete file: ' . $filepath);
        }

        // Output
        $this->info('Finished');

        return;
    }

    /**
     * Truncate the tables by getting chunks of results and deleting the records.
     *
     * @return void
     */
    protected function truncateTables(): void
    {
        // Output
        $this->info('Truncating old data, this may take some time...');

        // Chunk and delete
        City::chunkById(5000, function($cities) {
            foreach($cities as $city) {
                $city->delete();
            }
        });
        State::chunkById(1000, function($states) {
            foreach($states as $state) {
                $state->delete();
            }
        });
        Country::chunkById(1000, function($countries) {
            foreach($countries as $country) {
                $country->delete();
            }
        });
    }
}