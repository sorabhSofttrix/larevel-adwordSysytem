<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountStatusColumnToTheadwordsAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adwords_accounts', function (Blueprint $table) {
            $table->enum('acc_status', ['requiredSetup','active','paused','closed'])->default('requiredSetup');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('adwords_accounts', function (Blueprint $table) {
            $table->dropColumn('acc_status');
        });
    }
}
