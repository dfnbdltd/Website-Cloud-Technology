<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 120)->nullable()->after('name');
            $table->string('last_name', 120)->nullable()->after('first_name');
            $table->string('phone', 40)->nullable();
            $table->string('company_name', 190)->nullable();
            $table->string('address1', 190)->nullable();
            $table->string('address2', 190)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('postcode', 30)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('found_us', 190)->nullable();
            $table->string('security_question', 255)->nullable();
            $table->string('security_answer', 255)->nullable();
            $table->boolean('newsletter')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'company_name', 'address1',
                'address2', 'city', 'country', 'state', 'postcode', 'currency',
                'found_us', 'security_question', 'security_answer', 'newsletter',
            ]);
        });
    }
}
