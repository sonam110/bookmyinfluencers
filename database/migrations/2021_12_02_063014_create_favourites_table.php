<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFavouritesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('favourites', function (Blueprint $table) {
            $table->id();
            $table->boolean('plateform_type')->default('1')->nullable()->comment('1:youtube', '2:instagram', '3:tiktok');
            $table->bigInteger('user_id');
            $table->bigInteger('channel_id');
            $table->tinyInteger('status')->comment('1:Yes,0:No');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('favourites');
    }

}
