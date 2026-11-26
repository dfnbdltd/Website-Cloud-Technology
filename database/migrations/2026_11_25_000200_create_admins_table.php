<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin accounts. AdminController::login (phase 5) checks this table first and
 * falls back to the .env ADMIN_EMAIL / ADMIN_PASSWORD when no row matches.
 */
class CreateAdminsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('admins')) {
            return;
        }

        Schema::create('admins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 120);
            $table->string('email', 190)->unique();
            $table->string('password', 255);          // bcrypt hash, never plain text
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admins');
    }
}
