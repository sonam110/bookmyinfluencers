<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppliedCampaignsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('applied_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->boolean('plateform_type')->default('1')->nullable()->comment('1:youtube', '2:instagram', '3:tiktok');
            $table->foreignId('influ_id')->comment('User Table id/login user');
            $table->integer('brand_id')->comment('User Table id');
            $table->integer('camp_id');
            $table->integer('channel_id');
            $table->boolean('old_duration')->default('0')->comment('1:Agree,0:Disagree');
            $table->string('new_duration')->nullable();
            $table->boolean('promotion_slot')->default('0')->comment('1:Agree,0:Disagree');
            $table->string('new_promotion_slot')->nullable();
            $table->string('currency')->default('INR');
            $table->float('price')->default('0');
            $table->boolean('view_commitment')->default('0')->comment('1:Yes,0:No');
            $table->string('min_views')->nullable();
            $table->boolean('minor_changes')->default('0')->comment('1:Yes,0:No');
            $table->string('delivery_days')->nullable();
            $table->string('other_delivery_days')->nullable();
            $table->boolean('social_media_share')->default('0')->comment('1:Yes,0:No');
            $table->text('social_media')->nullable();
            $table->boolean('privacy_policy')->default('0')->comment('1:Yes,0:No');
            $table->text('comment')->nullable();
            $table->enum('status', ['0', '1', '2', '3', '4', '5'])->default('0')->comment('0:Pending,1:Hire,2:ShortList,3:Reject,4:Incomplete,5:Complete');
            $table->foreign('influ_id')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('applied_campaigns');
    }

}
