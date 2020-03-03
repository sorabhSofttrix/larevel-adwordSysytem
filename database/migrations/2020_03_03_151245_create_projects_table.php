<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('project_name',255)->nullable();
            $table->dateTime('contract_start_date')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('weekly_limit', 8, 2)->nullable();
            $table->text('questionnaire')->nullable();
            
            $table->unsignedBigInteger('sales_person')->nullable();
            $table->foreign('sales_person')
                ->references('id')->on('users');

            $table->unsignedBigInteger('profile')->nullable();
            $table->foreign('profile')
                ->references('id')->on('profiles');
            
            $table->unsignedBigInteger('client')->nullable();
            $table->foreign('client')
                ->references('id')->on('clients');
                
            $table->unsignedBigInteger('add_by')->nullable();
            $table->foreign('add_by')
                ->references('id')->on('users');

            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('projects');
    }
}
