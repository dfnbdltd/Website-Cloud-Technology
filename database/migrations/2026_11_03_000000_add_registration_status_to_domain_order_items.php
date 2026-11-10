<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegistrationStatusToDomainOrderItems extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('domain_order_items') || Schema::hasColumn('domain_order_items', 'registration_status')) {
            return;
        }

        Schema::table('domain_order_items', function (Blueprint $table) {
            $table->string('registration_status')->default('pending');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('domain_order_items') || !Schema::hasColumn('domain_order_items', 'registration_status')) {
            return;
        }

        Schema::table('domain_order_items', function (Blueprint $table) {
            $table->dropColumn('registration_status');
        });
    }
}
