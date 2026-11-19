<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organization Name Consent is asked once per domain.
 *
 *   org_consent_choice        'accepted' ("Yes, I agree") or 'declined' ("Cancel")
 *   org_consent_responded_at  when ResellerClub accepted the answer
 *
 * Both stay NULL until the customer answers, so every existing domain still
 * shows the consent box exactly once.
 */
class AddOrgConsentToDomainOrderItems extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('domain_order_items')) {
            return;
        }

        if (!Schema::hasColumn('domain_order_items', 'org_consent_choice')) {
            Schema::table('domain_order_items', function (Blueprint $table) {
                $table->string('org_consent_choice', 20)->nullable();
            });
        }

        if (!Schema::hasColumn('domain_order_items', 'org_consent_responded_at')) {
            Schema::table('domain_order_items', function (Blueprint $table) {
                $table->timestamp('org_consent_responded_at')->nullable();
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('domain_order_items')) {
            return;
        }

        foreach (['org_consent_choice', 'org_consent_responded_at'] as $column) {
            if (Schema::hasColumn('domain_order_items', $column)) {
                Schema::table('domain_order_items', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
}
