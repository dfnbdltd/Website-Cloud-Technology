<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SSL plans always offer 1, 2 and 3 year terms now, so the six per-term
 * "active" / "visible" flags are dead weight. This drops them.
 *
 * WHY NOT $table->dropColumn(...)
 * -------------------------------
 * Laravel 6's SQLite grammar implements dropColumn through doctrine/dbal
 * (SQLiteGrammar::compileDropColumn calls getDoctrineSchemaManager()), and this
 * project does not install doctrine/dbal — it throws
 * "Laravel v6 is only compatible with doctrine/dbal 2". The test suite runs on
 * SQLite, so a plain dropColumn would fail every migration-driven test.
 *
 * Instead the table is rebuilt: create the new shape, copy the surviving rows,
 * drop the old table, rename. That is ordinary DDL, so it behaves the same on
 * MySQL (production) and SQLite (tests). Ids and feature references are
 * preserved by copying `id` explicitly.
 */
class DropSslPlanTermFlags extends Migration
{
    /** Columns being removed. */
    private $drop = [
        'term_one_year_active',
        'term_two_year_active',
        'term_three_year_active',
        'term_one_year_visible',
        'term_two_year_visible',
        'term_three_year_visible',
    ];

    public function up()
    {
        if (!Schema::hasTable('ssl_plans')) {
            return;
        }

        // Already migrated (re-run after a partial deploy): nothing to do.
        foreach ($this->drop as $column) {
            if (!Schema::hasColumn('ssl_plans', $column)) {
                return;
            }
        }

        $this->rebuildWithout(collect($this->drop));
    }

    public function down()
    {
        if (!Schema::hasTable('ssl_plans')) {
            return;
        }

        if (Schema::hasColumn('ssl_plans', 'term_one_year_active')) {
            return;
        }

        Schema::table('ssl_plans', function (Blueprint $table) {
            $table->boolean('term_one_year_active')->default(true);
            $table->boolean('term_two_year_active')->default(true);
            $table->boolean('term_three_year_active')->default(true);
            $table->boolean('term_one_year_visible')->default(true);
            $table->boolean('term_two_year_visible')->default(true);
            $table->boolean('term_three_year_visible')->default(true);
        });
    }

    /**
     * Rebuild ssl_plans with `$remove` left out, preserving every other column
     * and all rows. Uses copy → drop → rename so it never needs dbal.
     */
    private function rebuildWithout($remove)
    {
        $keep = collect($this->columnsOf('ssl_plans'))
            ->reject(function ($column) use ($remove) {
                return $remove->contains($column);
            })
            ->values()
            ->all();

        $temporary = 'ssl_plans_rebuild';

        Schema::dropIfExists($temporary);

        Schema::create($temporary, function (Blueprint $table) use ($keep) {
            // Same shape as the original migration, minus the removed flags.
            $table->increments('id');
            $table->string('plan_key', 80)->unique();
            $table->string('name', 120);
            $table->string('tagline', 255)->nullable();
            $table->string('badge', 80)->nullable();
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->decimal('renewal_price', 12, 2)->default(0);
            $table->decimal('discount_one_year', 5, 2)->default(0);
            $table->decimal('discount_two_year', 5, 2)->default(5);
            $table->decimal('discount_three_year', 5, 2)->default(10);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        $this->copyRows($temporary, $keep);

        Schema::drop('ssl_plans');
        Schema::rename($temporary, 'ssl_plans');
    }

    /**
     * Insert-then-select copy. Done with the query builder rather than
     * "INSERT ... SELECT" because MySQL and SQLite disagree on that syntax.
     */
    private function copyRows($temporary, array $keep)
    {
        \Illuminate\Support\Facades\DB::table('ssl_plans')
            ->orderBy('id')
            ->chunk(200, function ($rows) use ($temporary, $keep) {
                $payload = [];

                foreach ($rows as $row) {
                    $record = [];
                    foreach ($keep as $column) {
                        $record[$column] = $row->{$column};
                    }
                    $payload[] = $record;
                }

                if ($payload) {
                    \Illuminate\Support\Facades\DB::table($temporary)->insert($payload);
                }
            });
    }

    /**
     * Column names for a table, read from the driver's own schema so this works
     * identically on MySQL and SQLite without dbal.
     */
    private function columnsOf($table)
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            return array_map(function ($column) {
                return $column->name;
            }, $connection->select('PRAGMA table_info("' . $table . '")'));
        }

        return array_map(function ($column) {
            return $column->Field;
        }, $connection->select('SHOW COLUMNS FROM `' . $table . '`'));
    }
}
