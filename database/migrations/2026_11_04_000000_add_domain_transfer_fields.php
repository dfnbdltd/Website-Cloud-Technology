<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDomainTransferFields extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('domain_order_items')) {
            return;
        }

        if (!Schema::hasColumn('domain_order_items', 'order_type')) {
            Schema::table('domain_order_items', function (Blueprint $table) {
                $table->string('order_type')->default('registration');
            });
        }

        if (!Schema::hasColumn('domain_order_items', 'transfer_auth_code')) {
            Schema::table('domain_order_items', function (Blueprint $table) {
                $table->text('transfer_auth_code')->nullable();
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('domain_order_items')) {
            return;
        }

        foreach (['order_type', 'transfer_auth_code'] as $column) {
            if (Schema::hasColumn('domain_order_items', $column)) {
                Schema::table('domain_order_items', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
}
