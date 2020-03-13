<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHourBillingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hour_billings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('acc_id');
            $table->foreign('acc_id')
                    ->references('id')->on('adwords_accounts');
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')
                    ->references('id')->on('projects');
            $table->decimal('hours', 8, 2);
            $table->string('stage', 255)->nullable();
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
        Schema::dropIfExists('hour_billings');
    }
}
