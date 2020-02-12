<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyToHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_change_histories', function (Blueprint $table) {
            $table->foreign('acc_id')
              ->references('id')->on('adwords_accounts')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_change_histories', function (Blueprint $table) {
            $table->dropForeign(['acc_id']);
        });
    }
}
