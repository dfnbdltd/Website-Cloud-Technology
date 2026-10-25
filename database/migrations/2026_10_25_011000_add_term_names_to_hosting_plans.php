<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The plan card NAME becomes per billing term.
 *
 * Until now `name` was one column shown on every billing-term view, so
 * renaming a card from the Yearly view renamed it on Monthly as well.
 * From now on:
 *
 *   - `name` stays the Monthly name (the card's canonical name).
 *   - `name_yearly` / `name_two_year` / `name_three_year` hold that term's
 *     own name once an admin saves it from the term's Edit page; a NULL
 *     column means the term still shows the Monthly name (same inherit
 *     rule as the per-term feature rows).
 *
 * No backfill needed — NULL inherits `name`, so pages render exactly as
 * they did before this migration.
 */
class AddTermNamesToHostingPlans extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hosting_plans')) {
            return;
        }

        if (!Schema::hasColumn('hosting_plans', 'name_yearly')) {
            Schema::table('hosting_plans', function ($table) {
                $table->string('name_yearly', 120)->nullable();
            });
        }
        if (!Schema::hasColumn('hosting_plans', 'name_two_year')) {
            Schema::table('hosting_plans', function ($table) {
                $table->string('name_two_year', 120)->nullable();
            });
        }
        if (!Schema::hasColumn('hosting_plans', 'name_three_year')) {
            Schema::table('hosting_plans', function ($table) {
                $table->string('name_three_year', 120)->nullable();
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('hosting_plans')) {
            return;
        }

        foreach (['name_yearly', 'name_two_year', 'name_three_year'] as $column) {
            if (Schema::hasColumn('hosting_plans', $column)) {
                Schema::table('hosting_plans', function ($table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
}
