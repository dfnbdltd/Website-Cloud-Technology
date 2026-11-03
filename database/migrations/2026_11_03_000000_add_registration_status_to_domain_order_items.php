<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegistrationStatusToDomainOrderItems extends Migration
{
    public function up()
    {
        Schema::table('domain_order_items', function (Blueprint $table) {
            $table->string('registration_status')->default('pending');
        });
    }

    public function down()
    {
        Schema::table('domain_order_items', function (Blueprint $table) {
            $table->dropColumn('registration_status');
        });
    }
}
