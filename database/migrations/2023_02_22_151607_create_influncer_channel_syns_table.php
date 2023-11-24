<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInfluncerChannelSynsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('influncer_channel_syns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('influ_id');
            $table->foreign('influ_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('internal_channel_id')->nullable();
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
        Schema::dropIfExists('influncer_channel_syns');
    }
}
