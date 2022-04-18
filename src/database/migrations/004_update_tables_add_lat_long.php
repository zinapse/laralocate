<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{

    /**
     * Which tables we're adding to.
     *
     * @var array
     */
    public array $tables = [
        'countries', 'states', 'cities'
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add lat and long to each table
        foreach($this->tables as $table) {
            Schema::table('laralocate_' . $table, function (Blueprint $table) {
                $table->string('lat')->nullable()->before('created_at');
                $table->string('long')->nullable()->before('created_at');
            });
        }
    }
 
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the columns
        foreach($this->tables as $table) {
            Schema::table('laralocate_' . $table, function (Blueprint $table) {
                $table->dropColumn('lat');
                $table->dropColumn('long');
            });
        }
    }
};