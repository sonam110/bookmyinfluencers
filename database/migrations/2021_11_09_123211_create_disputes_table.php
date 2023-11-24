<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisputesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('reason')->nullable();
            $table->string('reason1')->nullable();
            $table->text('comment')->nullable();
            $table->float('amount')->nullable();
            $table->enum('status', ['0', '1', '2'])->default('1')->comment('0:Open,1:Close');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('disputes');
    }

}
