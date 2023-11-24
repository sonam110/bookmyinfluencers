<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionHistoriesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('transaction_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->comment('User Table id');
            $table->string('transaction_id');
            $table->tinyInteger('type')->comment('1:wallet,2:credit,3:campaign,4:channel,5:application,6:invitation,7:hire,8:order,9:message,10:payment:10:topup,11:ShortList,12:whatsapp request,13:added to,14:Revealed')->nullable();
            $table->tinyInteger('bal_type')->comment('1:credit,2:debit,3:other')->default('credit');
            $table->float('old_amount')->default(0);
            $table->float('amount')->default(0);
            $table->float('new_amount')->default(0);
            $table->enum('status', ['0', '1'])->default('1')->comment('0:Failed,1:Success');
            $table->text('message')->nullable();
            $table->text('comment')->nullable();
            $table->string('currency')->nullable();
            $table->text('resource')->nullable();
            $table->integer('resource_id')->nullable()->comment('table name');
            $table->integer('created_by')->nullable()->comment('table primary key');
            $table->boolean('payment_mode')->nullable()->comment('0:wallet,1:Gateway Online');
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
        Schema::dropIfExists('transaction_histories');
    }

}
