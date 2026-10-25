<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Card VISIBILITY becomes per billing term — the last shared dimension.
 *
 * Until now `is_visible` hid a card from EVERY billing-term view at once
 * (monthly, yearly, 2 years and 3 years), so hiding Shared's 2 Years
 * deleted the card from the Monthly page too. From now on every term
 * controls its own visibility:
 *
 *   - `term_monthly_visible` / `term_yearly_visible` / `term_two_year_visible`
 *     / `term_three_year_visible` — false removes the card from THAT term's
 *     view only (list keeps it, other terms keep rendering it).
 *   - Hiding a card from all four terms hides it from the pages completely.
 *   - The old global `is_visible` value is copied into all four columns so
 *     cards hidden before behave exactly the same, then `is_visible` itself
 *     is no longer read by the code (kept in the schema for safety).
 *
 * No FKs, matching the rest of this schema.
 */
class AddTermVisibilityToHostingPlans extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hosting_plans')) {
            return;
        }

        foreach (['monthly', 'yearly', 'two_year', 'three_year'] as $term) {
            $column = 'term_' . $term . '_visible';
            if (!Schema::hasColumn('hosting_plans', $column)) {
                Schema::table('hosting_plans', function ($table) use ($column) {
                    $table->boolean($column)->default(true);
                });
            }
        }

        // Cards hidden by the old global flag stay hidden on every term.
        DB::transaction(function () {
            foreach (['monthly', 'yearly', 'two_year', 'three_year'] as $term) {
                $column = 'term_' . $term . '_visible';
                DB::table('hosting_plans')
                    ->where('is_visible', false)
                    ->update([$column => false]);
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('hosting_plans')) {
            return;
        }

        // Fold the per-term flags back into the global column before dropping.
        DB::table('hosting_plans')->update([
            'is_visible' => DB::raw('term_monthly_visible AND term_yearly_visible AND term_two_year_visible AND term_three_year_visible'),
        ]);

        foreach (['monthly', 'yearly', 'two_year', 'three_year'] as $term) {
            $column = 'term_' . $term . '_visible';
            if (Schema::hasColumn('hosting_plans', $column)) {
                Schema::table('hosting_plans', function ($table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
}
