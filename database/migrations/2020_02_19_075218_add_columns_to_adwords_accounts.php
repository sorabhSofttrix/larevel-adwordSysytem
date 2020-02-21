<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToAdwordsAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adwords_accounts', function (Blueprint $table) {
            $table->string('conversoin', 255)->nullable();
            $table->string('cpc', 255)->nullable();
            $table->string('totalConversion', 255)->nullable();
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
            $table->dropColumn('conversoin');
            $table->dropColumn('cpc');
            $table->dropColumn('totalConversion');
        });
    }
}
