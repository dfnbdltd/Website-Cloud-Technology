<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('session_id', 64)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('sender', 20); // customer | admin
            $table->text('message');
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
}
