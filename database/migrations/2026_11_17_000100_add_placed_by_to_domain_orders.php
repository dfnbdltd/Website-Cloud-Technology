<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remember who PLACED a domain order when it was bought for another customer.
 *
 * `user_id` stays the owner (the domain shows in their My Store); this column
 * lets the buyer still open their own order confirmation.
 *
 * Skips itself when the column already exists, because the same change ships as
 * database/ssl_orders_and_buy_for_customer.sql for installs that apply SQL by
 * hand. down() is a documented no-op (dropColumn needs doctrine/dbal on
 * Laravel 6 + SQLite; the column is nullable so leaving it is harmless).
 */
class AddPlacedByToDomainOrders extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('domain_orders') || Schema::hasColumn('domain_orders', 'placed_by_user_id')) {
            return;
        }

        Schema::table('domain_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('placed_by_user_id')->nullable()->index()->after('user_id');
        });

        DB::table('domain_orders')
            ->whereNull('placed_by_user_id')
            ->whereNotNull('user_id')
            ->update(['placed_by_user_id' => DB::raw('user_id')]);
    }

    public function down()
    {
        //
    }
}
