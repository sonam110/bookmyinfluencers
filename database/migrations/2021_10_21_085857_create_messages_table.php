<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->comment('User Table id')->nullable();
            $table->foreignId('receiver_id')->comment('User Table id')->nullable();
            $table->integer('order_id')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default('0')->comment('1:Yes,0:No');
            $table->boolean('is_system_generated')->default('0')->comment('0:System Generated,1:Auto Generated');
            $table->integer('status')->nullable()->comment('0:Pending,1:Review,2:Submit,3:Request Change,4:Approved,5:Cancel ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('messages');
    }

}
