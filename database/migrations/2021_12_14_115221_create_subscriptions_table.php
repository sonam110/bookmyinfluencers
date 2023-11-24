<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id');
            $table->foreignId('user_id');
            $table->integer('amount')->unsigned()->default(0);
            $table->string('tax');
            $table->float('tax_amount')->default('0');
            $table->float('total')->default('0');
            $table->string('pg_order_id', 100)->nullable()->comment('payment gateway order id');
            $table->timestamp('subscribed_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Created at');
            $table->dateTime('expire_at')->nullable();
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
        Schema::drop('subscriptions');
    }

}
