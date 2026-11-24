<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Mark as hosted": the admin sets the hosting up by hand, then marks the order
 * Hosted. The time is saved in hosted_at; the optional admin_note (for example
 * the cPanel username) is saved and emailed to the customer.
 *
 * Safe to run twice, and safe to run after database/hosting_orders_hosted.sql.
 */
class AddHostedFieldsToHostingOrders extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hosting_orders')) {
            return;
        }

        Schema::table('hosting_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('hosting_orders', 'hosted_at')) {
                $table->timestamp('hosted_at')->nullable();
            }
            if (!Schema::hasColumn('hosting_orders', 'admin_note')) {
                $table->text('admin_note')->nullable();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('hosting_orders')) {
            return;
        }

        Schema::table('hosting_orders', function (Blueprint $table) {
            if (Schema::hasColumn('hosting_orders', 'admin_note')) {
                $table->dropColumn('admin_note');
            }
            if (Schema::hasColumn('hosting_orders', 'hosted_at')) {
                $table->dropColumn('hosted_at');
            }
        });
    }
}
