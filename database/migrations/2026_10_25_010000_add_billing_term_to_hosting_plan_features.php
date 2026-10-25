<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Features become per-card × per-billing-term.
 *
 * Until now a card stored ONE feature row per feature (value_monthly +
 * value_yearly on the same row), so editing a feature from e.g. the
 * Dedicate → Yearly view rewrote what Monthly showed as well. From now on
 * every card owns its own row set per billing term:
 *
 *   - `billing_term` marks which term a row set belongs to (monthly /
 *     yearly / two_year / three_year).
 *   - A term with no rows inherits the Monthly set at read time, so pages
 *     look unchanged until an admin saves features for that term.
 *   - `value_yearly` is retired: every existing row is duplicated as a
 *     Yearly row carrying the row's historical yearly line, so today's
 *     yearly output is preserved and Yearly becomes its own scope.
 *
 * The `value_monthly` column keeps holding the row's full line text
 * ("300 GB bandwidth/month") for every term.
 */
class AddBillingTermToHostingPlanFeatures extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hosting_plan_features')) {
            return;
        }

        if (!Schema::hasColumn('hosting_plan_features', 'billing_term')) {
            Schema::table('hosting_plan_features', function ($table) {
                $table->string('billing_term', 10)->default('monthly');
            });
        }

        DB::transaction(function () {
            // Preserve per-set order: rows keep their original ids and the
            // yearly twins are appended per plan, so each term's insertion
            // order matches the previous display.
            $rows = DB::table('hosting_plan_features')->orderBy('id')->get();

            foreach ($rows as $row) {
                $line = trim((string) $row->value_monthly);
                $differs = $row->value_yearly !== null && trim((string) $row->value_yearly) !== '' && trim((string) $row->value_yearly) !== $line;
                $yearlyLine = $differs ? trim((string) $row->value_yearly) : $line;

                $update = ['billing_term' => 'monthly'];
                if ($line !== (string) $row->value_monthly) {
                    $update['value_monthly'] = $line;
                }

                DB::table('hosting_plan_features')->where('id', $row->id)->update($update);

                // The historical yearly view = every Monthly row, with the
                // rows whose yearly line differed carrying that line. A
                // complete set is materialised so Yearly becomes its own
                // editable scope without changing what it showed before.
                DB::table('hosting_plan_features')->insert([
                    'hosting_plan_id' => $row->hosting_plan_id,
                    'feature_group' => $row->feature_group,
                    'name' => $row->name,
                    'value_monthly' => mb_substr($yearlyLine, 0, 190),
                    'feature_key' => $row->feature_key,
                    'is_available' => $row->is_available,
                    'billing_term' => 'yearly',
                    'created_at' => $row->created_at ?: date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('hosting_plan_features')) {
            return;
        }

        if (Schema::hasColumn('hosting_plan_features', 'billing_term')) {
            Schema::table('hosting_plan_features', function ($table) {
                $table->dropColumn('billing_term');
            });
        }
    }
}
