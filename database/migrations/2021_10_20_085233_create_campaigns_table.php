<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->boolean('plateform_type')->default('1')->nullable()->comment('1:youtube', '2:instagram', '3:tiktok');
            $table->foreignId('brand_id')->comment('User Table id');
            $table->enum('plateform', ['youtube', 'instagram', 'tiktok'])->default('youtube');
            $table->string('brand_name')->nullable();
            $table->string('brand_url')->nullable();
            $table->string('brand_logo')->nullable();
            $table->string('camp_title')->unique();
            $table->text('camp_desc')->nullable();
            $table->string('duration')->nullable();
            $table->string('promotion_start')->nullable();
            $table->string('promot_product')->nullable();
            $table->boolean('script_approval')->default('1')->comment('1:Yes,0:No');
            $table->longtext('reference_videos')->nullable();
            $table->text('category')->nullable();
            $table->text('tags')->nullable();
            $table->integer('subscriber')->nullable();
            $table->integer('followers')->nullable()->nullable();
            $table->integer('inf_score')->nullable()->nullable();
            $table->string('average_view')->nullable();
            $table->string('currency')->default('INR');
            $table->float('budget')->default(0);
            $table->string('deal_type')->nullable();
            $table->string('compensation')->nullable();
            $table->text('compensation_desc')->nullable();
            $table->string('country')->nullable();
            $table->integer('views_commitment')->nullable();
            $table->float('engagement_rate')->default(0);
            $table->string('lang')->nullable();
            $table->enum('status', ['0', '1', '2', '3', '4', '5'])->default('0')->comment('0:Inactive,1:Active,2:Invite Only,3:Draft,4:Reject,5:expired');
            $table->text('reason')->nullable();
            $table->boolean('invite_only')->default('0');
            $table->integer('visibility')->nullable();
            $table->timestamp('created_on')->nullable();
            $table->timestamp('expired_on')->nullable();
            $table->foreign('brand_id')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('campaigns');
    }

}
