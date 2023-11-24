<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListingsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->boolean('plateform_type')->default('1')->nullable()->comment('1:youtube', '2:instagram', '3:tiktok');
            $table->foreignId('brand_id')->comment('User Table');
            $table->integer('list_id');
            $table->integer('channel_id');
            $table->string('channel_url')->nullable();
            $table->foreign('brand_id')->references('id')->on('users')->onDelete('restrict');
            $table->enum('status', ['0', '1'])->default('1')->comment('0:Removed,1:Added');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('listings');
    }

}
