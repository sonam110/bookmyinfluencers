<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInfluencerYoutubeInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('influencer_youtube_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('influ_id');
            $table->foreign('influ_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('internal_channel_id')->nullable();
            $table->integer('last_10_views')->nullable();
            $table->integer('last_20_views')->nullable();
            $table->integer('last_30_views')->nullable();
            $table->integer('latest_5_views')->nullable();
            $table->integer('latest_10_views')->nullable();
            $table->integer('latest_30_views')->nullable();
            $table->integer('averageView')->nullable();
            $table->string('title')->nullable();
            $table->string('canonical_name')->nullable();
            $table->text('description')->nullable();
            $table->text('profile_pic')->nullable();
            $table->integer('viewCount')->nullable();
            $table->integer('subscriberCount')->nullable();
            $table->integer('videoCount')->nullable();
            $table->longText('lastetViewsVideos')->nullable();
            $table->longText('mostViewsVideos')->nullable();
            $table->longText('featuredVideo')->nullable();
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
        Schema::dropIfExists('influencer_youtube_infos');
    }
}
