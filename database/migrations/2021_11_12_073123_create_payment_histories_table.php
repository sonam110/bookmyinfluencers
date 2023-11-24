<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentHistoriesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->comment('User Table id');
            $table->enum('type', ['wallet', 'credit','payment'])->default('wallet');
            $table->enum('bal_type', ['credit', 'deduct'])->default('credit');
            $table->string('plan_id')->integer();
            $table->string('customer_id')->integer();
            $table->string('order_id')->nullable();
            $table->string('receipt')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('entity');
            $table->string('currency')->nullable();
            $table->float('old_amount')->default(0);
            $table->float('amount')->default(0);
            $table->float('new_amount')->default(0);
            $table->float('total')->default(0);
            $table->string('invoice_id')->nullable();
            $table->string('method')->nullable();
            $table->text('description')->nullable();
            $table->string('refund_status')->nullable();
            $table->float('amount_refunded')->default(0);
            $table->boolean('captured')->default('1')->comment('1:true,0:false');
            $table->string('email')->nullable();
            $table->string('contact')->nullable();
            $table->integer('fee')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('tax_amount')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_description')->nullable();
            $table->string('error_reason')->nullable();
            $table->string('card_id')->nullable();
            $table->longtext('card_info')->nullable();
            $table->string('bank')->nullable();
            $table->string('wallet')->nullable();
            $table->string('vpa')->nullable();
            $table->text('acquirer_data')->nullable();
            $table->enum('status', ['created', 'authorized', 'captured', 'refunded', 'failed'])->default('created');
            $table->enum('payemnt_status', ['pending', 'success', 'failed'])->default('pending');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('payment_histories');
    }

}
