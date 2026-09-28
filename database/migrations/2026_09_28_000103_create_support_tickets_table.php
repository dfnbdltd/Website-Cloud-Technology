<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupportTicketsTable extends Migration
{
    public function up()
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('subject');
            $table->text('message');
            $table->string('status')->default('open')->index();
            $table->string('priority')->default('normal');
            $table->timestamps();
        });
    }

    public function down() { Schema::dropIfExists('support_tickets'); }
}