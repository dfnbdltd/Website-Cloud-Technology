<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hosting flow, phase 0 (all nullable, so old orders keep working):
 *   plan_specs             snapshot of the storage spec at purchase ("10 GB | NVMe Storage")
 *   admin_seen_at          set when an admin has opened the order (drives the new-order badge)
 *   hosted_by_admin_id     admin who pressed "Mark as hosted"
 *   hosted_by_admin_email  that admin's email
 *
 * Safe to run twice, and safe to run after database/hosting_flow_phase0.sql.
 */
class AddPlanSpecsAndAdminFieldsToHostingOrders extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hosting_orders')) {
            return;
        }

        Schema::table('hosting_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('hosting_orders', 'plan_specs')) {
                $table->string('plan_specs', 255)->nullable();
            }
            if (!Schema::hasColumn('hosting_orders', 'admin_seen_at')) {
                $table->timestamp('admin_seen_at')->nullable()->index();
            }
            if (!Schema::hasColumn('hosting_orders', 'hosted_by_admin_id')) {
                $table->unsignedBigInteger('hosted_by_admin_id')->nullable();
            }
            if (!Schema::hasColumn('hosting_orders', 'hosted_by_admin_email')) {
                $table->string('hosted_by_admin_email', 190)->nullable();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('hosting_orders')) {
            return;
        }

        Schema::table('hosting_orders', function (Blueprint $table) {
            foreach (['hosted_by_admin_email', 'hosted_by_admin_id', 'admin_seen_at', 'plan_specs'] as $column) {
                if (Schema::hasColumn('hosting_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
