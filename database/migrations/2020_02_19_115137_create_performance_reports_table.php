<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePerformanceReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('performance_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('acc_id');
            $table->string('g_id', 255);

            $table->string('report_type', 255)->nullable();

            $table->string('cpa', 255)->nullable();
            $table->string('cost', 255)->nullable();
            $table->string('impressions', 255)->nullable();
            $table->string('click', 255)->nullable();
            $table->string('conversoin', 255)->nullable();
            $table->string('cpc', 255)->nullable();
            $table->string('totalConversion', 255)->nullable();

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
        Schema::dropIfExists('performance_reports');
    }
}
