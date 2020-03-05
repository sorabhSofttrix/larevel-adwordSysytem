<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdwordsAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adwords_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('g_acc_id', 255)->nullable();
            $table->string('acc_name', 255)->nullable();
            $table->string('budget', 255)->nullable();
            $table->string('cpa', 255)->nullable();
            $table->string('conversion_rate', 255);
            $table->string('cron_time', 255)->default('24');
            $table->string('priority', 255);
            $table->unsignedBigInteger('account_director')->nullable();
            $table->unsignedBigInteger('account_manager')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('adwords_accounts');
    }
}
