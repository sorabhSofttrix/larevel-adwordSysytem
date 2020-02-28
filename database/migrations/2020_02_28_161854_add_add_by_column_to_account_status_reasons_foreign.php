<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAddByColumnToAccountStatusReasonsForeign extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_status_reasons', function (Blueprint $table) {
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
        Schema::table('account_status_reasons', function (Blueprint $table) {
            //
            $table->dropForeign(['add_by']);
        });
    }
}
