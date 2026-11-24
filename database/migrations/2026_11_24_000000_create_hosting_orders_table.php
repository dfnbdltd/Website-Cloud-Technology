<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hosting plan orders (bKash payment flow, steps 1-3).
 *
 * One row = one hosting plan for one domain name.
 *
 *   user_id            the OWNER of the hosting (when buying for a customer, that customer)
 *   placed_by_user_id  who pressed Place order and pays
 *   total              BDT charged, frozen when the order is placed
 *   status             payment_pending -> awaiting_setup (paid) -> (later: processing -> active) | cancelled
 *   payment_status     pending -> initiated -> paid | cancelled
 *   customer_email     always the OWNER's account email
 *
 * Safe to run twice, and safe to run after database/hosting_orders.sql.
 */
class CreateHostingOrdersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('hosting_orders')) {
            return;
        }

        Schema::create('hosting_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference', 40)->unique();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('placed_by_user_id')->nullable()->index();

            $table->string('region', 20);
            $table->string('type', 20);
            $table->string('plan_id', 80);
            $table->string('plan_name', 160);
            $table->string('product', 120);
            $table->string('period', 20);
            $table->string('period_label', 40);
            $table->unsignedInteger('months')->default(1);
            $table->string('domain');

            $table->decimal('total', 12, 2);

            $table->string('status', 40)->default('payment_pending')->index();
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_status', 30)->default('pending')->index();
            $table->string('provider', 30)->nullable();
            $table->string('provider_payment_id')->nullable()->index();
            $table->text('payment_response')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hosting_orders');
    }
}
