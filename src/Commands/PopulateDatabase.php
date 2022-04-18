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
    protected $signature = 'laralocate:populate
                            {--c|cities : Be more verbose when adding cities}';
 
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
            $new_country->lat = $country->latitude;
            $new_country->long = $country->longitude;
            $new_country->save();
            $this->line('<fg=yellow>Added country: ' . $country->name . '</>');

            // Skip countries without states
            if(empty($country->states)) continue;
            
            // Iterate through the states
            $states = $country->states;
            foreach($states as $state) {
                // Only get "real" states
                $real = ($state->type == 'state' || empty($state->type));
                if(!$real) continue;

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