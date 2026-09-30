<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SMTP profiles, mirroring the ERP's dfn_mail_setup table. The enabled row
 * with the lowest id overrides the .env MAIL_* settings at boot.
 */
class CreateMailSetupsTable extends Migration
{
    public function up()
    {
        Schema::create('mail_setups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('m_host', 128);
            $table->string('m_username', 128);
            $table->string('m_password', 128);
            $table->integer('m_port');
            $table->enum('smtp_auth', ['0', '1'])->default('1');
            $table->string('smtp_secure', 8)->default('');
            $table->string('display_name', 128);
            $table->string('mail_address', 128);
            $table->string('mail_subject')->default('');
            $table->mediumText('mail_body')->nullable();
            $table->string('mail_type', 20)->default('reg');
            $table->enum('status', ['Enable', 'Disable', 'Delete'])->default('Enable');
            $table->dateTime('system_date')->nullable();
            $table->integer('default_id')->default(0);
            $table->integer('user_id')->default(0);
            $table->integer('com_id')->default(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mail_setups');
    }
}
