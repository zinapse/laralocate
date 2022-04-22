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
        Schema::table('laralocate_countries', function (Blueprint $table) {
            $table->integer('phone_code')->nullable()->before('created_at');
            $table->string('currency_name')->nullable()->before('created_at');
            $table->string('currency_symbol')->nullable()->before('created_at');
        });
    }
 
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the columns
        Schema::table('laralocate_countries', function (Blueprint $table) {
            $table->dropColumn('phone_code');
            $table->dropColumn('currency_name');
            $table->dropColumn('currency_symbol');
        });
    }
};