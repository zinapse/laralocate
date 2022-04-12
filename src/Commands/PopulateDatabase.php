<?php

namespace Zinapse\LaraLocate\Commands;

use Illuminate\Console\Command;
use Zinapse\LaraLocate\Models\LaraLocate;
use Zinapse\LaraLocate\Models\LaraLocateType;

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
        // Truncate the existing data
        LaraLocateType::truncate();
        LaraLocate::truncate();

        // Output
        $this->info('Populating location types');

        // Create a record for each object type
        foreach($this->object_types as $type) LaraLocateType::insertOrIgnore([ 'name' => $type ]);

        // Output
        $this->info('Downloading JSON file');

        // Download the JSON file required
        $filepath = tempnam(sys_get_temp_dir(), 'world_info.json') ?: 'world_info.json';
        if(!copy(config('laralocate.file_url'), $filepath)) {
            $this->error('Unable to download world data file');
            return;
        }

        // Output
        $this->info('Parsing JSON');

        // Parse the countries
        $json = json_decode(file_get_contents($filepath));
        foreach($json as $country) {
            // Add the country record
            $new_country = new LaraLocate;
            $new_country->name = $country->name;
            $new_country->code = $country->iso2;
            $new_country->laralocate_type = LaraLocateType::where('name', 'Country')->first()->id;
            $new_country->save();
            $this->info('Added country: ' . $country->name);

            // Skip countries without states
            if(empty($country->states)) continue;
            
            // Iterate through the states
            $states = $country->states;
            foreach($states as $state) {
                // Add the state record
                $new_state = new LaraLocate;
                $new_state->name = $country->name;
                $new_state->code = $country->iso2;
                $new_state->laralocate_type = LaraLocateType::where('name', 'State')->first()->id;
                $new_state->parent_id = $new_country->id;
                $new_state->save();
                $this->info('Added state: ' . $state->name);

                // Iterate through the cities
                $cities = $state->cities;
                foreach($cities as $city) {
                    LaraLocate::insertOrIgnore([
                        'name' => $city->name,
                        'laralocate_type' => LaraLocateType::where('name', 'City')->first()->id,
                        'parent_id' => $new_state->id
                    ]);
                    $this->info('Added city: ' . $city->name);
                }
            }
        }

        if(!unlink($filepath)) {
            $this->warn('Failed to delete file: ' . $filepath);
        }
    }
}