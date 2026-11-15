<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remember each domain's own ResellerClub order ID.
 *
 * Domain activity is read from ResellerClub by order ID. Looking the ID up by
 * domain name on every page view is slow, and resolving it from "the first item
 * of the cart order" returned the wrong domain's activity when one order held
 * several domains. The ID is now stored per item, the first time it is resolved.
 */
class AddResellerOrderIdToDomainOrderItems extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('domain_order_items') || Schema::hasColumn('domain_order_items', 'reseller_order_id')) {
            return;
        }

        Schema::table('domain_order_items', function (Blueprint $table) {
            $table->string('reseller_order_id', 32)->nullable();
        });
    }

    public function down()
    {
        if (!Schema::hasTable('domain_order_items') || !Schema::hasColumn('domain_order_items', 'reseller_order_id')) {
            return;
        }

        Schema::table('domain_order_items', function (Blueprint $table) {
            $table->dropColumn('reseller_order_id');
        });
    }
}
