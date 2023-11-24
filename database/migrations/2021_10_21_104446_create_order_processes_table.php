<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderProcessesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('order_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->string('orderRandId')->nullable();
            $table->boolean('plateform_type')->default('1')->nullable()->comment('1:youtube', '2:instagram', '3:tiktok');
            $table->integer('application_id');
            $table->foreignId('influ_id')->comment('User Table id role influ');
            $table->foreignId('brand_id')->comment('User Table id role client');
            $table->integer('camp_id');
            $table->integer('channel_id');
            $table->longtext('job_description')->nullable();
            $table->string('template_script')->nullable();
            $table->timestampt('camp_script_approval_date')->nullable();
            $table->longtext('video_script')->nullable();
            $table->timestampt('video_script_approval_date')->nullable();
            $table->string('video_script_desc')->nullable();
            $table->timestampt('video_prev_approval_date')->nullable();
            $table->string('video_preview')->nullable();
            $table->timestampt('live_video_approval_date')->nullable();
            $table->string('live_video')->nullable();
            $table->text('promo_text_link')->nullable();
            $table->boolean('is_text_link_provided')->default('0')->comment('1:Yes,0:No');
            $table->integer('stage')->default(1);
            $table->string('action_taken')->default(1);
            $table->text('comment')->nullable();
            $table->enum('status', ['0', '1', '2', '3', '4', '5'])->default('0')->comment('0:Pending,1:Review,2:Submit,3:Request Change,4:Approved,5:Cancel');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('order_processes');
    }

}
