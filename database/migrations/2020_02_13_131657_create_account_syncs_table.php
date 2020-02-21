<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountSyncsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_syncs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('add_by');
            $table->enum('status', ['success','failed','invalid','noDataToUpdate','empty']);
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
        Schema::dropIfExists('account_syncs');
    }
}
