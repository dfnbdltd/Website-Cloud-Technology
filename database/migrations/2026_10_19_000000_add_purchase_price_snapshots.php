<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPurchasePriceSnapshots extends Migration
{
    public function up()
    {
        Schema::table('domain_order_items', function (Blueprint $table) {
            $table->decimal('purchase_price', 12, 2)->nullable();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('purchase_price', 12, 2)->nullable();
        });

        DB::table('domain_order_items')->whereNull('purchase_price')->update([
            'purchase_price' => DB::raw('price'),
        ]);

        DB::table('invoice_items')->whereNull('purchase_price')->update([
            'purchase_price' => DB::raw('unit_price'),
        ]);
    }

    public function down()
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('purchase_price');
        });

        Schema::table('domain_order_items', function (Blueprint $table) {
            $table->dropColumn('purchase_price');
        });
    }
}
