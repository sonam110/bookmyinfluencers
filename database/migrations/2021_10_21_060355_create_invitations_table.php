<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->boolean('plateform_type')->default('1')->nullable()->comment('1:youtube', '2:instagram', '3:tiktok');
            $table->foreignId('brand_id')->comment('User Table id');
            $table->integer('camp_id')->comment('Campaign Table id');
            $table->integer('influ_id')->nullable();
            $table->string('channel_id')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['0', '1', '2'])->default('0')->comment('0-Pening,1-Accept,2-Reject');
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
        Schema::dropIfExists('invitations');
    }

}
