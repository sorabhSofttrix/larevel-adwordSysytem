<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MarkAccAsForeingKeyInAccountIssues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_issues', function (Blueprint $table) {
            //
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
        Schema::table('account_issues', function (Blueprint $table) {
            //
            $table->dropForeign(['acc_id']);
        });
    }
}
