<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountStatusChangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_status_changes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('new_value', 255)->nullable();
            $table->string('old_value', 255)->nullable();
            $table->text('comment')->nullable();
            $table->text('up_comments')->nullable();
            $table->decimal('rating', 8, 1)->nullable();
            
            $table->unsignedBigInteger('add_by');
            $table->foreign('add_by')
                ->references('id')->on('users');

            $table->unsignedBigInteger('reason_id');
            $table->foreign('reason_id')
                ->references('id')->on('account_status_reasons');
            $table->text('reason_text')->nullable();
            
            $table->unsignedBigInteger('acc_id');
            $table->foreign('acc_id')
              ->references('id')->on('adwords_accounts');
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
        Schema::dropIfExists('account_status_changes');
    }
}