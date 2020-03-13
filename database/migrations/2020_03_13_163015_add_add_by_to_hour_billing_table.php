<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAddByToHourBillingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hour_billings', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('add_by');
            $table->foreign('add_by')
                    ->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hour_billings', function (Blueprint $table) {
            //
            $table->dropColumn('add_by');
        });
    }
}
