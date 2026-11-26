<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * invoice_items.period_label: "Monthly", "1 Year", "2 Years", "3 Years".
 * Where it is set the invoice shows it instead of the years number
 * (hosting rows). Domain invoices leave it NULL and keep showing years.
 */
class AddPeriodLabelToInvoiceItems extends Migration
{
    public function up()
    {
        if (Schema::hasTable('invoice_items') && !Schema::hasColumn('invoice_items', 'period_label')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->string('period_label', 40)->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'period_label')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropColumn('period_label');
            });
        }
    }
}
