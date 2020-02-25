<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountIssuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_issues', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('acc_id');
            $table->text('error')->nullable();
            $table->boolean('status')->default(true);
            $table->text('comments')->nullable();
            $table->text('resolved_by')->nullable();
            $table->text('resolved_at')->timestamp()->nullable();
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
        Schema::dropIfExists('account_issues');
    }
}
