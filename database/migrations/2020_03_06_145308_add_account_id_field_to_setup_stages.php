<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountIdFieldToSetupStages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('setup_stages', function (Blueprint $table) {
            $table->unsignedBigInteger('acc_id');
            $table->foreign('acc_id')
                    ->references('id')->on('adwords_accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('setup_stages', function (Blueprint $table) {
            $table->dropColumn('acc_id');
        });
    }
}
