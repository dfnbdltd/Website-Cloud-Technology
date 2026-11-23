<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hosting plans get a REGION.
 *
 * The hosting menu is now  Hosting → Local Hosting / USA Hosting / WordPress
 * Hosting.  Local and USA each hold Shared, VPS and Dedicated plans; WordPress
 * has one simple list of cards.
 *
 *   region = 'local'      type = shared | vps | dedicated   (every existing row)
 *   region = 'usa'        type = shared | vps | dedicated   (new, starts empty)
 *   region = 'wordpress'  type = 'wordpress'                (new, starts empty)
 *
 * Existing plans are NOT changed: they all become region 'local', so today's
 * pages keep their plans and prices. USA and WordPress start empty; the admin
 * fills them from /admin/hosting-plans (there is a "Copy from Local" button).
 *
 * Safe to run twice, and safe to run after database/hosting_regions.sql.
 */
class AddRegionToHostingPlans extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hosting_plans')) {
            return;
        }

        if (!Schema::hasColumn('hosting_plans', 'region')) {
            Schema::table('hosting_plans', function ($table) {
                $table->string('region', 20)->default('local')->after('type');
                $table->index('region', 'hosting_plans_region_index');
            });
        }

        DB::table('hosting_plans')
            ->where(function ($query) {
                $query->whereNull('region')->orWhere('region', '');
            })
            ->update(['region' => 'local']);
    }

    public function down()
    {
        if (!Schema::hasTable('hosting_plans') || !Schema::hasColumn('hosting_plans', 'region')) {
            return;
        }

        // Plans that only exist for USA / WordPress have no place in the old schema.
        DB::table('hosting_plans')->where('region', '!=', 'local')->get()->each(function ($plan) {
            DB::table('hosting_plan_features')->where('hosting_plan_id', $plan->id)->delete();
        });
        DB::table('hosting_plans')->where('region', '!=', 'local')->delete();

        Schema::table('hosting_plans', function ($table) {
            $table->dropIndex('hosting_plans_region_index');
            $table->dropColumn('region');
        });
    }
}
