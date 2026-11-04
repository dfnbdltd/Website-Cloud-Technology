<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDomainTransferFields extends Migration
{
    public function up()
    {
        Schema::table('domain_order_items', function (Blueprint $table) {
            $table->string('order_type')->default('registration');
            $table->text('transfer_auth_code')->nullable();
        });
    }

    public function down()
    {
        Schema::table('domain_order_items', function (Blueprint $table) {
            $table->dropColumn(['order_type', 'transfer_auth_code']);
        });
    }
}
