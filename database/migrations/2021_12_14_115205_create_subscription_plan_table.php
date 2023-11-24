<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionPlanTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('subscription_plan', function (Blueprint $table) {
            $table->id();
            $table->boolean('campaigns')->default(1)->comment('Simultaneous campaign count(up to)');
            $table->integer('credits')->unsigned()->default(250)->comment('Amount of Credit assigned to plan');
            $table->boolean('credit_rollover')->default(1)->comment('0-no rollover, 1 - rollover');
            $table->boolean('multi_user')->default(1)->comment('Simultaneity login from multiple browser/device');
            $table->integer('signup_bonus')->unsigned()->default(0)->comment('In amount or inr');
            $table->integer('min_order_value')->unsigned()->default(50000)->comment('Min order value or amount in INR');
            $table->boolean('dispute_resolution')->default(0)->comment('0-no, 1- yes');
            $table->boolean('deal_coordination')->default(0)->comment('0-no, 1- yes');
            $table->integer('additional_credits')->nullable()->comment('Amount per 5000 credit');
            $table->boolean('order_payments')->default(100)->comment('Percent value');
            $table->boolean('premium')->default(0)->comment('0-no, 1 - full access');
            $table->boolean('customer_support')->default(1)->comment('1-email, 2-email,chat, 3-dedicated manager');
            $table->boolean('campaign_approval')->comment('0-moderated, 1- auto approved');
            $table->integer('price')->unsigned()->default(0)->comment('Billed Annually');
            $table->integer('extra_credit_price')->unsigned()->default(0)->comment('Extra credit price');
            $table->string('name', 45)->comment('Name of campaign');
            $table->boolean('whatsapp_introductions')->default(0)->nullable()->comment('0-NO, 1-Yes');
            $table->boolean('campaign_visibility')->default(0)->nullable()->comment('0-NO, 1-Yes');
            $table->string('campaign_approval_hrs')->nullable();
            $table->boolean('status')->default(1)->comment('0-Inactive, 1- Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('subscription_plan');
    }

}
