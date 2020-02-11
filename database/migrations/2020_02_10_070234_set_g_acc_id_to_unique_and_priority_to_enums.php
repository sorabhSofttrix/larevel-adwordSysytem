<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetGAccIdToUniqueAndPriorityToEnums extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adwords_accounts', function (Blueprint $table) {
            $table->unique('g_acc_id');
            $table->dropColumn('priority');
            $table->enum('acc_priority', ['low', 'normal','moderate', 'high', 'urgent']);
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
            $table->dropUnique('g_acc_id');
            $table->string('priority', 255);
            $table->dropColumn('acc_priority');
        });
    }
}
