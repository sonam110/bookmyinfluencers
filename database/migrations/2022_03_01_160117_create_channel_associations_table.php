<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelAssociationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('channel_association', function (Blueprint $table) {
            $table->id();
            $table->boolean('plateform_type')->default('1')->nullable()->comment('1:youtube', '2:instagram', '3:tiktok');
            $table->foreignId('influ_id');
            $table->foreignId('user_id');
            $table->foreignId('internal_channel_id');
            $table->tinyInteger('is_verified')->default(0);
            $table->string('yt_key')->nullable();
            $table->tinyInteger('type')->default(0)->comment('0-private, 1- public');
            $table->float('promotion_price')->default(0);
            $table->tinyInteger('is_default')->default('0')->comment('1:enable,0:disable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('channel_association');
    }

}
