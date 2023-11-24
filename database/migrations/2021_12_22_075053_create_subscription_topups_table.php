<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionTopupsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('subscription_topups', function (Blueprint $table) {
            $table->bigInteger('id', true)->unsigned()->comment('Primary key');
            $table->integer('subscription_plan_current_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->float('amount')->default(0);
            $table->string('tax');
            $table->float('tax_amount')->default('0');
            $table->float('total')->default('0');
            $table->string('pg_order_id', 100)->nullable()->comment('payment gateway order id');
            ;
            $table->boolean('status')->default(1)->comment('0-failed, 1- Active, 2-Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('subscription_topups');
    }

}
