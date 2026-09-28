<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDomainOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('domain_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference')->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->decimal('total', 12, 2);
            $table->string('status')->default('payment_pending')->index();
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending')->index();
            $table->string('provider')->nullable();
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('customer_name');
            $table->string('customer_phone', 30);
            $table->string('customer_email');
            $table->text('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down() { Schema::dropIfExists('domain_orders'); }
}