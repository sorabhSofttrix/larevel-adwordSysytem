<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAllCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('all_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('entity_type',255);
            $table->unsignedBigInteger('entity_id');
            $table->text('comment');
            $table->unsignedBigInteger('add_by')->nullable();
            $table->foreign('add_by')
                ->references('id')->on('users');
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('parent_id')->nullable();
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
        Schema::dropIfExists('all_comments');
    }
}
