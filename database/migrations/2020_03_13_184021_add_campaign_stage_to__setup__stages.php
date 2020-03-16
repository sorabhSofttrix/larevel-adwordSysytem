<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCampaignStageToSetupStages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('setup_stages', function (Blueprint $table) {
            $table->boolean('campaign_live')->default(false);
            $table->unsignedBigInteger('campaign_live_by')->nullable();
            $table->foreign('campaign_live_by')
                        ->references('id')->on('users');
            $table->datetime('campaign_live_on')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('setup_stages', function (Blueprint $table) {
            $table->dropColumn('campaign_live');
            $table->dropColumn('campaign_live_by');
            $table->dropColumn('campaign_live_on');
        });
    }
}
