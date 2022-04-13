<?php

namespace Zinapse\LaraLocate\Commands;

use Illuminate\Console\Command;
use Zinapse\LaraLocate\Models\City;
use Zinapse\LaraLocate\Models\Country;
use Zinapse\LaraLocate\Models\State;

class PopulateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laralocate:populate';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the LaraLocate tables';

    /**
     * An array of types to add for LaraLocateTypes.
     *
     * @var array
     */
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
        // Check if we already have data
        $country = Country::first() ?? null;
        if(empty($country) && !$this->confirm('LaraLocate data already exists, would you like to continue and overwrite the existing data?', true)) return;

        // Truncate the existing data
        City::truncate();
        State::truncate();
        Country::truncate();

        // Output
        $this->info('Downloading JSON file');

        // Download the JSON file required
        $file_url = config('laralocate.file_url');
        $filepath = tempnam(sys_get_temp_dir(), 'world_info.json') ?: 'world_info.json';
        if(!copy($file_url, $filepath)) {
            $this->error('Unable to download world data file from: ' . $file_url);
            return;
        }

        // Output
        $this->info('Parsing JSON');

        // Parse the countries
        $json = json_decode(file_get_contents($filepath));
        foreach($json as $country) {
            // Add the country record
            $new_country = new Country;
            $new_country->name = $country->name;
            $new_country->code = $country->iso2;
            $new_country->save();
            $this->info('Added country: ' . $country->name);

            // Skip countries without states
            if(empty($country->states)) continue;
            
            // Iterate through the states
            $states = $country->states;
            foreach($states as $state) {
                // Add the state record
                $new_state = new State;
                $new_state->name = $country->name;
                $new_state->code = $country->iso2;
                $new_state->parent_id = $new_country->id;
                $new_state->save();
                $this->info('Added state: ' . $state->name);

                // Iterate through the cities
                $cities = $state->cities;
                foreach($cities as $city) {
                    $new_city = new City;
                    $new_city->name = $city->name;
                    $new_city->parent_id = $new_state->id;
                    $new_city->save();
                    $this->info('Added city: ' . $city->name);
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
}