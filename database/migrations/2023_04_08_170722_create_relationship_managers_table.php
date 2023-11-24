<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRelationshipManagersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('relationship_managers', function (Blueprint $table) {
            $table->id();
            $table->boolean('type')->default(1)->comment('1:brand,2:Influncer');
            $table->string('fullname');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->integer('plan_id')->nullable();
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
        Schema::dropIfExists('relationship_managers');
    }
}
