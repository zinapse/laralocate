<?php

namespace Zinapse\LaraLocate\Commands;

use Exception;
use OsmPbf\Reader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Zinapse\LaraLocate\Models\LaraLocate;

class PopulateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laralocate:populate
                            {--country=* : Include this country}
                            {--continent=* : Include this continent}';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the LaraLocate tables';

    /**
     * An array mapping countries to their latest .pbf file URLs from geofabrik.
     *
     * @var array
     */
    protected $pbf_mapping = [
        'africa'            => 'https://download.geofabrik.de/africa-latest.osm.pbf',
        'antarctica'        => 'https://download.geofabrik.de/antarctica-latest.osm.pbf',
        'asia'              => 'https://download.geofabrik.de/asia-latest.osm.pbf',
        'australia'         => 'https://download.geofabrik.de/australia-oceania-latest.osm.pbf',
        'europe'            => 'https://download.geofabrik.de/europe-latest.osm.pbf',
        'central_america'   => 'https://download.geofabrik.de/central-america-latest.osm.pbf',
        'north_america'     => 'https://download.geofabrik.de/north-america-latest.osm.pbf',
        'south_america'     => 'https://download.geofabrik.de/south-america-latest.osm.pbf',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Check if we have the table for laralocate
            $existing = LaraLocate::all();

            // If we have existing data, make sure the user wants to overwrite it
            if(!empty($existing)) {
                if(!$this->confirm('This will overwrite existing laralocate data, would you like to continue?', true)) {
                    $this->warn('Exitting, no data has changed');
                    return;
                }
                
                // Truncate the table
                LaraLocate::truncate();
            }

        } catch(Exception $e) {}

        // Populate the table
        foreach($this->pbf_mapping as $continent => $pbf_url) {
            // Output
            $this->info("Downloading $continent from: $pbf_url");

            // Define our file names and path
            $filename = "$continent.osm.pbf";
            $filepath = tempnam(sys_get_temp_dir(), $filename) ?: $filename;

            // Create the file from the URL
            if(!copy($pbf_url, $filepath)) {
                $this->error('Error while downloading file: ' . $filename);
                return;
            }

            // Output
            $this->info('File downloaded, parsing...');

            // Define our file handler and the pbf reader
            $file_handler = fopen($filepath, 'rb');
            $pbfreader = new Reader($file_handler);

            ////////////// TEST
            $this->info('Header data:' . print_r($pbfreader->readFileHeader()));

            // Delete the file once finished
            if(!unlink($filepath)) {
                $this->error('Error while deleting file: ' . $filepath);
                return;
            }

            // Output
            $this->info("Finished $continent");

            return;
        }
    }

    private function processElements($elements)
    {
        $type = $elements['type'];

        $records = [];
        $tags = [];
        $nodes = [];
        $relations = [];

        foreach ($elements['data'] as $element) {
            $insert_element = [
                'id' => $element['id'],
                'changeset_id' => $element['changeset_id'],
                'visible' => $element['visible'],
                'timestamp' => $element['timestamp'],
                'version' => $element['version'],
                'uid' => $element['uid'],
                'user' => $element['user'],
            ];
            if ($type == "node") {
                $insert_element["latitude"] = $element["latitude"];
                $insert_element["longitude"] = $element["longitude"];
            }
            if (isset($element["timestamp"])) {
                $insert_element["timestamp"] = str_replace("T", " ", $element["timestamp"]);
                $insert_element["timestamp"] = str_replace("Z", "", $element["timestamp"]);
            }
            $records[] = $insert_element;

            foreach ($element["tags"] as $tag) {
                $insert_tag = [
                    $type . "_id" => $element["id"],
                    "k" => $tag["key"],
                    "v" => $tag["value"]
                ];
                $tags[] = $insert_tag;
            }
            foreach ($element["nodes"] as $node) {
                $insert_node = [
                    $type . "_id" => $element["id"],
                    "node_id" => $node["id"],
                    "sequence" => $node["sequence"]
                ];
                $nodes[] = $insert_node;
            }

            foreach ($element["relations"] as $relation) {
                $insert_relation = [
                    $type . "_id" => $element["id"],
                    "member_type" => $relation["member_type"],
                    "member_id" => $relation["member_id"],
                    "member_role" => $relation["member_role"],
                    "sequence" => $relation["sequence"]
                ];
                $relations[] = $insert_relation;
            }
        }
    }
}