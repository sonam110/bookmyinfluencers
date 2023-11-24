<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('userType');
            $table->string('fullname');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verified_token')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('cover_photo')->nullable();
            $table->text('address')->nullable();
            $table->boolean('credit_reminder')->default('0')->comment('1:enable,0:disable');
            $table->boolean('notification')->default('0')->comment('1:enable,0:disable');
            $table->string('default_bank')->nullable();
            $table->string('token')->nullable();
            $table->float('wallet_balance')->default('0');
            $table->float('reserved_balance')->default('0');
            $table->float('remaining_balance')->default('0');
            $table->float('credit_balance')->default('0');
            $table->timestamp('last_topup_date')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('currency')->nullable();
            $table->float('ops_currency_rate')->nullable();
            $table->string('ops_currency')->nullable();
            $table->text('category_preferences')->nullable();
            $table->integer('manager_id')->nullable();
            $table->tinyInter('status')->default('1')->comment('0:Inactice,1:Active,2:Deleted,3:Incomplete');
            $table->tinyInter('account_type')->default('0')->comment('0:Private,1:Public');
            $table->tinyInter('is_login_first_time')->default('1')->comment('0:false,1:true');
            $table->boolean('is_google')->default('0')->comment('0:No,1:Yes');
            $table->boolean('dont_show_me_again')->default('0')->nullable()->comment('0:No,1:Yes');
            $table->string('ip_address')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('users');
    }

}
