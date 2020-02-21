<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAdwordsAccountFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adwords_accounts', function (Blueprint $table) {
            $table->string('cost', 255)->nullable();
            $table->string('impressions', 255)->nullable();
            $table->string('click', 255)->nullable();
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
            $table->dropColumn('cost');
            $table->dropColumn('click');
            $table->dropColumn('impressions');
        });
    }
}
