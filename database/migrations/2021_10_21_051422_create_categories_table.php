<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('User Table id');
            $table->integer('is_parent')->nullable();
            $table->enum('status', ['0', '1'])->default('1')->comment('0-Inactive,1-Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('categories');
    }

}
