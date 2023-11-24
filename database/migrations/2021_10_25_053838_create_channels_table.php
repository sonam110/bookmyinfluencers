<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('channel_id')->nullable();
            $table->string('channel')->nullable();
            $table->string('canonical_name')->nullable();
            $table->enum('plateform', ['youtube', 'instagram'])->default('youtube');
            $table->string('channel_link');
            $table->string('channel_lang')->nullable();
            $table->string('channel_name')->nullable();
            $table->string('channel_email')->nullable();
            $table->string('public_profile_url')->nullable();
            $table->text('tags')->nullable();
            $table->string('image')->nullable();
            $table->string('name')->nullable();
            $table->string('number')->nullable();
            $table->string('email')->nullable();
            $table->string('channel_lang')->nullable();
            $table->string('blur_image')->nullable();
            $table->text('yt_description')->nullable();
            $table->string('views')->nullable();
            $table->string('subscribers')->nullable();
            $table->string('videos')->nullable();
            $table->string('views_sub_ratio')->nullable();
            $table->string('engagementrate')->nullable();
            $table->string('price_view_ratio')->nullable();
            $table->text('cat_percentile_1')->nullable();
            $table->text('cat_percentile_5')->nullable();
            $table->text('cat_percentile_10')->nullable();
            $table->text('tag_category')->nullable();
            $table->string('image_path')->nullable();
            $table->string('credit_cost')->nullable();
            $table->string('exposure')->nullable();
            $table->string('currency')->nullable();
            $table->string('language')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();
            $table->string('inf_recommend')->nullable();
            $table->string('onehit')->nullable();
            $table->string('oldcontent')->nullable();
            $table->date('currentdate')->nullable();
            $table->float('fair_price')->nullable();
            $table->enum('status', ['1', '0', '2'])->default('1')->comment('1:Active, 0:Inactive,2:Delete');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('channels');
    }

}
