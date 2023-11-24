<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderInvoiceManualPaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_invoice_manual_pays', function (Blueprint $table) {
            $table->id();
            $table->string('orderRandId')->nullable();
            $table->boolean('plateform_type')->default('1')->nullable()->comment('1:youtube', '2:instagram', '3:tiktok');
            $table->foreignId('application_id');
            $table->foreignId('influ_id')->comment('User Table id role influ');
            $table->foreignId('brand_id')->comment('User Table id role client');
            $table->integer('camp_id');
            $table->integer('channel_id');
            $table->boolean('payment_term')->default('0')->comment('1:50% pay,0:100% pay');
            $table->string('invoice')->nullable();
            $table->float('camp_price')->default('0');
            $table->float('pay_amount')->default('0');
            $table->string('currency')->default('INR');
            $table->string('tax');
            $table->float('tax_amount')->default('0');
            $table->float('total_pay')->default('0');
            $table->boolean('payment_status')->default('0')->comment('0:Pending,1:Success,2:Cancelled,3:Failed');
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
        Schema::dropIfExists('odrer_invoice_manual_pays');
    }
}
