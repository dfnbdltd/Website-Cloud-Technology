<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('domain_order_id')->index();
            $table->string('reference')->unique();
            $table->string('provider');
            $table->string('provider_payment_id')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->text('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down() { Schema::dropIfExists('payments'); }
}