<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTopupsTable extends Migration
{
    public function up()
    {
        Schema::create('topups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('reference', 40)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('method', 30)->default('bkash');
            $table->string('sender_number', 40)->nullable();
            $table->string('transaction_id', 60)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('topups');
    }
}
