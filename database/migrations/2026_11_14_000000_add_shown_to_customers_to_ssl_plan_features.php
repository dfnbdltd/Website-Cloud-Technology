<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-feature hide/show for SSL plan cards.
 *
 * WHY A NEW COLUMN
 * ----------------
 * `ssl_plan_features.is_available` already means something different and
 * customer-visible: a row with is_available = 0 is rendered with a red cross
 * next to it (the same idea as "Remote database access ✗" on the hosting
 * cards). Reusing it for "hide this feature" would silently restyle every
 * existing row the admin hid, so visibility gets its own flag.
 *
 * A single checkbox drives both, and the admin row keeps them consistent:
 *
 *   shown   -> is_available = 1, shown_to_customers = 1
 *   hidden  -> is_available = 0, shown_to_customers = 0
 *
 * Reading falls back to is_available when the new column is absent, so this
 * migration is safe to run after a deploy has already shipped the views.
 *
 * WHY NOT dropColumn IN down()
 * ----------------------------
 * Same reason as the two SSL migrations before this one: Laravel 6's SQLite
 * grammar implements dropColumn through doctrine/dbal, which this project does
 * not install, so it would throw on the SQLite test database. The column is
 * dropped by rebuilding the table instead, mirroring
 * 2026_11_12_000000_drop_ssl_plan_term_flags.
 */
class AddShownToCustomersToSslPlanFeatures extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('ssl_plan_features')) {
            return;
        }

        if (Schema::hasColumn('ssl_plan_features', 'shown_to_customers')) {
            return;
        }

        Schema::table('ssl_plan_features', function (Blueprint $table) {
            // Default 1: every feature that exists today keeps showing, so this
            // migration never changes a live page.
            $table->boolean('shown_to_customers')->default(true)->after('feature');
        });
    }

    /**
     * Rebuild the table without the column, without needing doctrine/dbal.
     */
    public function down()
    {
        if (!Schema::hasTable('ssl_plan_features') || !Schema::hasColumn('ssl_plan_features', 'shown_to_customers')) {
            return;
        }

        $temporary = 'ssl_plan_features_rebuild';
        Schema::dropIfExists($temporary);

        Schema::create($temporary, function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ssl_plan_id')->index();
            $table->string('feature', 190);
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('ssl_plan_features')
            ->orderBy('id')
            ->chunk(200, function ($rows) use ($temporary) {
                $payload = [];
                foreach ($rows as $row) {
                    $payload[] = [
                        'id' => $row->id,
                        'ssl_plan_id' => $row->ssl_plan_id,
                        'feature' => $row->feature,
                        'is_available' => $row->is_available,
                        'sort' => $row->sort,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }

                if ($payload) {
                    \Illuminate\Support\Facades\DB::table($temporary)->insert($payload);
                }
            });

        Schema::drop('ssl_plan_features');
        Schema::rename($temporary, 'ssl_plan_features');
    }
}
