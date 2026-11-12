<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Link an SSL plan to its ResellerClub product so prices can come from the API.
 *
 * WHY NOT dropColumn IN down()
 * ----------------------------
 * Same reason as the previous migration (2026_11_12_…): Laravel 6's SQLite
 * grammar implements dropColumn through doctrine/dbal, which this project does
 * not install, so it would throw on the SQLite test database. The `down()` is a
 * documented no-op; the column is nullable, so leaving it behind is harmless.
 *
 * WHY A NULLABLE COLUMN AND NOT A CONFIG MAP
 * ------------------------------------------
 * The pairing is editable from /admin/ssl-plans. A plan with no ID (or one that
 * ResellerClub does not return a price for) keeps using its typed BDT price, so
 * this migration can never break a page.
 *
 * The IDs below were read off the ResellerClub panel URLs
 * (`…/ssl-certificate/sslcert/<id>`) and the plan rows are matched by
 * `plan_key`. The seed migration only inserts into an EMPTY table, so a live
 * database may have renamed or extra keys — those rows are reported in the log
 * instead of failing the deploy.
 */
class AddResellerclubPlanIdToSslPlans extends Migration
{
    /**
     * plan_key => ResellerClub plan ID (USD/month prices from the API).
     *
     *   300 Standard SSL   299 Premium SSL   301 Wildcard SSL   672 EV SSL
     */
    private $map = [
        'positive-ssl' => 300,
        'comodo-ssl' => 299,
        'positive-ssl-wildcard' => 301,
        'ev-ssl' => 672,
    ];

    public function up()
    {
        if (!Schema::hasTable('ssl_plans')) {
            // Fresh install where the create migration has not run yet; that
            // migration will create the column-less table and this one is
            // re-run afterwards by the migrator in order anyway.
            return;
        }

        if (!Schema::hasColumn('ssl_plans', 'resellerclub_plan_id')) {
            Schema::table('ssl_plans', function (Blueprint $table) {
                $table->unsignedInteger('resellerclub_plan_id')->nullable()->after('badge');
            });
        }

        $this->backfill();
    }

    /**
     * Re-runnable: only fills rows that still have no plan ID, so an ID the
     * owner set (or cleared) in the admin form is never overwritten by a
     * later deploy.
     */
    private function backfill()
    {
        $matched = [];
        $missing = [];

        foreach ($this->map as $planKey => $planId) {
            $updated = DB::table('ssl_plans')
                ->where('plan_key', $planKey)
                ->whereNull('resellerclub_plan_id')
                ->update(['resellerclub_plan_id' => $planId]);

            if ($updated > 0) {
                $matched[] = $planKey . ' => ' . $planId;
                continue;
            }

            // Either the key does not exist, or it was already linked.
            $exists = DB::table('ssl_plans')->where('plan_key', $planKey)->exists();
            if (!$exists) {
                $missing[] = $planKey;
            }
        }

        Log::info('SSL ResellerClub plan IDs backfilled', [
            'linked' => $matched,
            'unmatched_plan_keys' => $missing,
        ]);

        if ($missing && app()->runningInConsole()) {
            $this->report($missing);
        }
    }

    /**
     * Tell the deployer exactly which rows still need an ID, because a silent
     * skip is how a plan ends up showing typed prices after the deploy.
     *
     * Migrations in Laravel 6 are not given an $output writer, so the console
     * line is echoed directly (only under the CLI — never into a web response).
     */
    private function report(array $missing)
    {
        echo 'SSL plans not linked by this migration (set their ResellerClub plan ID in /admin/ssl-plans): '
            . implode(', ', $missing) . PHP_EOL;
    }

    /**
     * No-op on purpose: see the class comment (dropColumn needs doctrine/dbal
     * on Laravel 6 + SQLite). The column is nullable and unread when absent.
     */
    public function down()
    {
        //
    }
}
