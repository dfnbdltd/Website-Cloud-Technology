<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What ResellerClub tells us once a transfer-in has finished.
 *
 *   expires_at            the domain's expiry date at ResellerClub (endtime)
 *   transfer_completed_at when ResellerClub finished the transfer
 *
 * Both are filled by DomainTransferSyncService; nothing reads them for a
 * registration, so existing rows stay valid with NULL.
 */
class AddTransferSyncFieldsToDomainOrderItems extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('domain_order_items')) {
            return;
        }

        foreach (['expires_at', 'transfer_completed_at'] as $column) {
            if (!Schema::hasColumn('domain_order_items', $column)) {
                Schema::table('domain_order_items', function (Blueprint $table) use ($column) {
                    $table->timestamp($column)->nullable();
                });
            }
        }

        // Transfers an admin already marked complete by hand (order "active") but whose
        // item still says "transfer_submitted": they are finished, so give them the same
        // status the sync would, which is what unlocks their manage page.
        if (Schema::hasTable('domain_orders')) {
            DB::table('domain_order_items')
                ->where('order_type', 'transfer')
                ->where('registration_status', 'transfer_submitted')
                ->whereIn('domain_order_id', function ($query) {
                    $query->select('id')->from('domain_orders')->where('status', 'active')->where('payment_status', 'paid');
                })
                ->update([
                    'registration_status' => 'transferred',
                    'transfer_completed_at' => now(),
                ]);
        }
    }

    public function down()
    {
        if (!Schema::hasTable('domain_order_items')) {
            return;
        }

        foreach (['expires_at', 'transfer_completed_at'] as $column) {
            if (Schema::hasColumn('domain_order_items', $column)) {
                Schema::table('domain_order_items', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
}
