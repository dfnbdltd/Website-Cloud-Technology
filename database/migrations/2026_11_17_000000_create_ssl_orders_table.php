<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SSL certificate orders.
 *
 * Domain orders live in domain_orders / domain_order_items and carry
 * registration- and transfer-specific columns, so SSL gets its own small table
 * instead of bending that one. One row = one certificate for one domain.
 *
 * Money is frozen at the moment the order is placed (see the note in
 * config/ssl_plans.php): `total` is the BDT amount the customer is charged,
 * `exchange_rate` the USD→BDT rate used, and `purchase_price` your ResellerClub
 * cost for the same term in BDT (null when the cost API was unreachable).
 *
 * `status` / `payment_status` follow the domain order vocabulary so the
 * payment-gateway step can reuse the same words:
 *   status          payment_pending → paid → processing → active | failed
 *   payment_status  pending → initiated → paid | cancelled
 *
 * `reseller_order_id` and `registration_status` are left empty on purpose: the
 * "place the order on ResellerClub after payment" step fills them in.
 */
class CreateSslOrdersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ssl_orders')) {
            return;
        }

        Schema::create('ssl_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference', 40)->unique();

            // Who owns the certificate, and who placed the order (they differ
            // when a reseller buys for one of their customers).
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('placed_by_user_id')->nullable()->index();

            $table->unsignedInteger('ssl_plan_id')->nullable();
            $table->string('plan_key', 80);
            $table->string('plan_name', 120);
            $table->unsignedInteger('resellerclub_plan_id')->nullable();

            $table->string('domain');
            $table->unsignedInteger('years')->default(1);

            $table->decimal('total', 12, 2);
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('exchange_rate', 12, 4)->nullable();
            $table->string('price_source', 20)->nullable();

            $table->string('status', 40)->default('payment_pending')->index();
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_status', 30)->default('pending')->index();
            $table->string('provider', 30)->nullable();
            $table->string('provider_payment_id')->nullable()->index();

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30)->nullable();

            $table->string('reseller_order_id', 40)->nullable();
            $table->string('registration_status', 40)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ssl_orders');
    }
}
