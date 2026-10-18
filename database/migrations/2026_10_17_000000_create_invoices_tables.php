<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-facing invoices.
 *
 * An invoice is the BILLABLE document; domain orders, top-ups and (later)
 * hosting orders only create/flip invoices — they keep working exactly as
 * before. `source_type` / `source_id` keep a plain reference back to what
 * produced the invoice (no real FK, matching the rest of this schema).
 *
 * Admins can edit line items (e.g. extend a domain from 3 to 4 years) while
 * the customer always sees the same invoice number via the hosted page
 * (/invoices/{id}) and can download it as PDF.
 */
class CreateInvoicesTables extends Migration
{
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('invoice_number', 20)->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->string('type', 20)->default('order')->index();
            $table->string('status', 20)->default('unpaid')->index();
            $table->string('payment_method', 30)->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('currency', 10)->default('BDT');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_percent', 5, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('invoice_id')->index();
            $table->unsignedBigInteger('sl_no');
            $table->string('description');
            $table->string('item_type', 30)->nullable();
            $table->unsignedInteger('years')->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
}
