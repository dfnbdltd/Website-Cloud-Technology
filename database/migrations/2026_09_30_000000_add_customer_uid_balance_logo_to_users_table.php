<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerUidBalanceLogoToUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'customer_uid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('customer_uid', 20)->nullable()->unique()->after('id');
                $table->decimal('balance', 12, 2)->default(0)->after('security_answer');
                $table->string('logo', 255)->nullable()->after('balance');
            });
        }

        // Backfill existing customers with a permanent unique ID (CT-000001, ...).
        \App\User::whereNull('customer_uid')->orderBy('id')->each(function ($user) {
            $user->forceFill(['customer_uid' => \App\User::generateUid()])->save();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['customer_uid', 'balance', 'logo']);
        });
    }
}
