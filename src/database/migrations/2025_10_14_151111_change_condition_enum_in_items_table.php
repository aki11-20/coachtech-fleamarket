<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeConditionEnumInItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('condition');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->enum('condition', ['良好', '目立った傷や汚れなし', 'やや傷や汚れあり', '状態が悪い'])->default('良好');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('condition');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->enum('condition', ['new', 'used'])->default('new');
        });
    }
}
