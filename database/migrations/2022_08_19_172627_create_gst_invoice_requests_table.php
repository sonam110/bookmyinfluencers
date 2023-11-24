<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGstInvoiceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gst_invoice_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('influ_id')->comment('User Table id role influ');
            $table->foreignId('brand_id')->comment('User Table id role client');
            $table->integer('camp_id');
            $table->integer('channel_id');
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
        Schema::dropIfExists('gst_invoice_requests');
    }
}
