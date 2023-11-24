<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClmsToUsersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->after('address')->nullable();
            $table->text('company_address')->after('company_name')->nullable();
            $table->string('skype')->after('company_address')->nullable();
            $table->string('whats_app')->after('skype')->nullable();
            $table->integer('country_id')->after('whats_app')->nullable();
            $table->string('currency')->after('country_id')->nullable();
            $table->string('pan_no')->after('currency')->nullable();
            $table->string('gst')->after('pan_no')->nullable();
            $table->string('bank_name')->after('gst')->nullable();
            $table->string('account_holder')->after('bank_name')->nullable();
            $table->string('account_number')->after('account_holder')->nullable();
            $table->string('ifsc_code')->after('account_number')->nullable();
            $table->string('upi_id')->after('ifsc_code')->nullable();
            $table->boolean('whats_app_notification')->after('upi_id')->default(0);
            $table->boolean('detail_in_exchange')->after('whats_app_notification')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('company_name');
            $table->dropColumn('company_address');
            $table->dropColumn('skype');
            $table->dropColumn('whats_app');
            $table->dropColumn('country_id');
            $table->dropColumn('currency');
            $table->dropColumn('pan_no');
            $table->dropColumn('gst');
        });
    }

}
