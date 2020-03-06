<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSetupStagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setup_stages', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->boolean('keywords')->default(false);
            $table->text('keywords_url')->nullable();
            $table->unsignedBigInteger('keywords_by')->nullable();
            $table->foreign('keywords_by')
                    ->references('id')->on('users');
            $table->datetime('keywords_on')->nullable();
            $table->string('keywords_score')->nullable();


            $table->boolean('adcopies')->default(false);
            $table->text('adcopies_url')->nullable();
            $table->unsignedBigInteger('adcopies_by')->nullable();
            $table->foreign('adcopies_by')
                    ->references('id')->on('users');
            $table->datetime('adcopies_on')->nullable();
            $table->string('adcopies_score')->nullable();


            $table->boolean('peer_review')->default(false);
            $table->unsignedBigInteger('peer_review_by')->nullable();
            $table->foreign('peer_review_by')
                    ->references('id')->on('users');
            $table->datetime('peer_review_on')->nullable();


            $table->boolean('client_keyad_review')->default(false);
            $table->unsignedBigInteger('client_keyad_review_by')->nullable();
            $table->foreign('client_keyad_review_by')
                    ->references('id')->on('users');
            $table->datetime('client_keyad_review_on')->nullable();

            $table->boolean('campaign_setup')->default(false);
            $table->unsignedBigInteger('campaign_setup_by')->nullable();
            $table->foreign('campaign_setup_by')
                    ->references('id')->on('users');
            $table->datetime('campaign_setup_on')->nullable();

            $table->boolean('client_review')->default(false);
            $table->unsignedBigInteger('client_review_confirmed_by')->nullable();
            $table->foreign('client_review_confirmed_by')
                        ->references('id')->on('users');
            $table->datetime('client_review_confirmed_on')->nullable();

            $table->boolean('conversion_tracking')->default(false);
            $table->unsignedBigInteger('conversion_tracking_by')->nullable();
            $table->foreign('conversion_tracking_by')
                        ->references('id')->on('users');
            $table->datetime('conversion_tracking_on')->nullable();

            $table->boolean('google_analytics')->default(false);
            $table->unsignedBigInteger('google_analytics_by')->nullable();
            $table->foreign('google_analytics_by')
                        ->references('id')->on('users');
            $table->datetime('google_analytics_on')->nullable();

            $table->boolean('gtm')->default(false);
            $table->unsignedBigInteger('gtm_by')->nullable();
            $table->foreign('gtm_by')
                        ->references('id')->on('users');
            $table->datetime('gtm_on')->nullable();

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
        Schema::dropIfExists('setup_stages');
    }
}
