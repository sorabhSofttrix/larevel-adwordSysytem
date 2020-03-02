<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHistoryIdColumnToAccountStatusChanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_status_changes', function (Blueprint $table) {
            $table->unsignedBigInteger('history_id');
            $table->foreign('history_id')
                ->references('id')->on('account_change_histories');
            $table->unsignedBigInteger('reason_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_status_changes', function (Blueprint $table) {
            //
            $table->dropForeign(['history_id']);
            $table->dropColumn('history_id');
        });
    }
}
