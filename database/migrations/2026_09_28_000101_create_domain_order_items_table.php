<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDomainOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('domain_order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('domain_order_id')->index();
            $table->string('domain');
            $table->string('tld', 40);
            $table->unsignedInteger('years')->default(1);
            $table->decimal('price', 12, 2);
            $table->decimal('renewal_price', 12, 2);
            $table->timestamps();
        });
    }

    public function down() { Schema::dropIfExists('domain_order_items'); }
}