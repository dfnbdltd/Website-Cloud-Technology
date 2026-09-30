<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Email verification is now required to sign in. Accounts that existed before
 * the feature shipped are treated as verified so nobody gets locked out.
 */
class VerifyExistingUsers extends Migration
{
    public function up()
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down()
    {
        //
    }
}
