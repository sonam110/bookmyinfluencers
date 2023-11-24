<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instagram_data', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('name');
            $table->text('bio');
            $table->string('category');
            $table->integer('followers');
            $table->integer('following');
            $table->integer('profile_post');
            $table->longText('following_profile');
            $table->integer('avg_likes')->nullable();
            $table->integer('engmnt_rate')->nullable();
            $table->integer('fair_price')->nullable();
            $table->integer('inf_score')->nullable();
            $table->string('website')->nullable();
            $table->string('emails')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender')->nullable();
            $table->string('language')->nullable();
            $table->string('country')->nullable();
            $table->string('currency')->nullable();
            $table->integer('inf_price')->nullable();
            $table->integer('pitching_price')->nullable();
            $table->integer('story_price')->nullable();
            $table->boolean('status')->nullable()->default(0)->comment('0=unconfirmed,1=confirmed,2=managed,3=follow,4=suspeded');
            $table->string('managedby')->nullable();
            $table->string('added_by')->nullable();
            $table->date('added_date')->nullable();
            $table->date('confirmed_on')->nullable();
            $table->date('updated_date')->nullable();
            $table->integer('keyword_status')->nullable();
            $table->boolean('autotag_status')->nullable()->default(0);
            $table->text('tags')->nullable();
            $table->boolean('tag_category_status')->nullable()->default(0);
            $table->text('tag_category')->nullable();
            $table->integer('inf_promotions')->nullable();
            $table->string('image')->nullable();
            $table->string('blur_image')->nullable();
            $table->string('credit_cost')->nullable();
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
        Schema::dropIfExists('instagram_data');
    }
}
