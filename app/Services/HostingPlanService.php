<?php

namespace App\Services;

use App\Models\HostingPlan;
use App\Models\HostingPlanFeature;
use Illuminate\Support\Facades\Schema;

/**
 * Single source of truth for hosting plan cards.
 *
 * Serves the shared / VPS / dedicated pages and the client-area compare
 * page. It converts the DB rows into the exact array shape the views have
 * always consumed (name/class/description/badge/prices/primary_features/
 * extra_features) plus, new under CMS control:
 *   - per feature `available` (check icon) vs false (red cross row)
 *   - per feature `feature_key` (data-feature, e.g. "ecommerce")
 *   - `terms()` telling which billing tabs render as "Coming soon"
 *
 * Fallback: while the tables are missing or have no rows at all for a type
 * (e.g. a deploy before `php artisan migrate`) the historical hardcoded
 * content is returned so the site renders exactly as it did before the
 * CMS. Once rows exist for a type, the CMS owns it — a hidden/deleted set
 * of plans then renders an empty grid rather than the old hardcode
 * silently coming back.
 */
class HostingPlanService
{
    /** @var bool|null memoized schema check */
    private static $tablesMissing = null;

    /** All visible plan cards of one hosting type, in admin-set order. */
    public function plans($type)
    {
        return $this->isManaged($type) ? $this->fromDatabase($type) : $this->legacy($type);
    }

    /**
     * PAGE-LEVEL billing-term status per period key (monthly / yearly /
     * twoYear / threeYear). A term is considered available on the page
     * when AT LEAST ONE visible plan of the type offers it — individual
     * cards flag their own availability through `plans()` below, so a
     * type can mix cards with and without a term.
     *
     * @return array period => ['active' => bool, 'soon' => bool]
     */
    public function terms($type)
    {
        $legacy = [
            'monthly' => ['active' => true, 'soon' => false],
            'yearly' => ['active' => true, 'soon' => false],
            'twoYear' => ['active' => false, 'soon' => true],
            'threeYear' => ['active' => false, 'soon' => true],
        ];

        if (!$this->isManaged($type)) {
            return $legacy;
        }

        $plans = HostingPlan::query()
            ->where('type', $type)
            ->where('is_visible', true)
            ->get();

        if ($plans->isEmpty()) {
            return $legacy;
        }

        $terms = [];
        foreach ([
            'monthly' => 'term_monthly_active',
            'yearly' => 'term_yearly_active',
            'twoYear' => 'term_two_year_active',
            'threeYear' => 'term_three_year_active',
        ] as $period => $column) {
            $active = $plans->contains(function (HostingPlan $plan) use ($column, $period) {
                return $plan->visibleFor($period) && (bool) $plan->{$column};
            });
            $terms[$period] = ['active' => $active, 'soon' => !$active];
        }

        return $terms;
    }

    /** @return array period => bool (term offered on this card) */
    private function planTerms(HostingPlan $row)
    {
        return [
            'monthly' => (bool) $row->term_monthly_active,
            'yearly' => (bool) $row->term_yearly_active,
            'twoYear' => (bool) $row->term_two_year_active,
            'threeYear' => (bool) $row->term_three_year_active,
        ];
    }

    /* ------------------------------------------------------------------ */
    /* CMS mode                                                            */
    /* ------------------------------------------------------------------ */

    private function isManaged($type)
    {
        if (self::$tablesMissing === null) {
            self::$tablesMissing = !Schema::hasTable('hosting_plans') || !Schema::hasTable('hosting_plan_features');
        }
        if (self::$tablesMissing) {
            return false;
        }

        return HostingPlan::query()->where('type', $type)->exists();
    }

    private function fromDatabase($type)
    {
        $rows = HostingPlan::query()
            ->where('type', $type)
            ->where('is_visible', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->with('features')
            ->get();

        $plans = [];

        foreach ($rows as $row) {
            $plans[] = [
                'name' => $row->name,
                'names' => [
                    'monthly' => $row->name,
                    'yearly' => $row->nameFor('yearly'),
                    'twoYear' => $row->nameFor('twoYear'),
                    'threeYear' => $row->nameFor('threeYear'),
                ],
                'class' => $row->style_class,
                'description' => (string) $row->description,
                'badge' => $row->badge,
                'prices' => [
                    'monthly' => $row->price_monthly,
                    'yearly' => $row->price_yearly,
                    'twoYear' => $row->price_two_year,
                    'threeYear' => $row->price_three_year,
                ],
                'terms' => $this->planTerms($row),
                'visibility' => [
                    'monthly' => $row->visibleFor('monthly'),
                    'yearly' => $row->visibleFor('yearly'),
                    'twoYear' => $row->visibleFor('twoYear'),
                    'threeYear' => $row->visibleFor('threeYear'),
                ],
                'primary_features' => $this->featuresForMeetings($row, 'monthly'),
                'extra_features' => $this->featuresForMeetings($row, 'extra'),
                'feature_sets' => $this->featureSets($row),
            ];
        }

        return $plans;
    }

    /**
     * The Monthly-view feature lists of a plan card (the shape the card
     * markup, specs line and compare table always consumed).
     */
    private function featuresForMeetings(HostingPlan $row, $list)
    {
        $out = [];
        foreach ($row->features as $feature) {
            $group = $feature->feature_group === HostingPlanFeature::GROUP_EXTRA ? 'extra' : 'primary';
            if ($list === 'extra' ? $group !== 'extra' : $group !== 'primary') {
                continue;
            }
            if ($feature->billing_term !== HostingPlanFeature::TERM_MONTHLY) {
                continue;
            }

            $line = trim((string) $feature->value_monthly);
            $fullYearly = $feature->value_yearly !== null && trim((string) $feature->value_yearly) !== ''
                ? trim((string) $feature->value_yearly)
                : $line;

            if ($group === 'primary') {
                [$leadMonthly, $rest] = self::leadAndRest($line);
                $leadYearly = self::leadAndRest($fullYearly)[0];
                $out[] = [
                    'monthly' => $leadMonthly !== '' ? $leadMonthly : $line,
                    'yearly' => $leadYearly !== '' ? $leadYearly : $line,
                    'name' => $rest,
                    'available' => (bool) $feature->is_available,
                    'feature_key' => $feature->feature_key,
                ];
            } else {
                $out[] = [
                    'monthly' => $line,
                    'yearly' => $fullYearly,
                    'name' => $feature->name,
                    'available' => (bool) $feature->is_available,
                    'feature_key' => $feature->feature_key,
                ];
            }
        }

        return $out;
    }

    /**
     * Per billing-term feature sets of one card: each term shows the rows
     * saved for THAT term (text, order and Available/Crossed included).
     * A term that was never saved inherits the Monthly set.
     *
     * @return array period => ['primary' => [...], 'extra' => [...]]
     */
    private function featureSets(HostingPlan $row)
    {
        $sets = [];
        foreach (['monthly', 'yearly', 'twoYear', 'threeYear'] as $period) {
            $slug = HostingPlanFeature::termSlug($period);
            $rows = $row->features->where('billing_term', $slug);
            if ($rows->isEmpty() && $period !== 'monthly') {
                $rows = $row->features->where('billing_term', HostingPlanFeature::TERM_MONTHLY);
            }
            $sets[$period] = $this->mapFeatureSet($rows);
        }

        return $sets;
    }

    /** Normalize one term's rows into blade-ready lists. */
    private function mapFeatureSet($rows)
    {
        $set = ['primary' => [], 'extra' => []];
        foreach ($rows as $feature) {
            $line = trim((string) $feature->value_monthly);
            $item = [
                'available' => (bool) $feature->is_available,
                'feature_key' => $feature->feature_key,
            ];
            if ($feature->feature_group === HostingPlanFeature::GROUP_EXTRA) {
                $item['text'] = $line;
                $set['extra'][] = $item;
            } else {
                [$lead, $rest] = self::leadAndRest($line);
                $item['lead'] = ($lead !== '' && $rest !== '') ? $lead : $line;
                $item['name'] = ($lead !== '' && $rest !== '') ? $rest : '';
                $set['primary'][] = $item;
            }
        }

        return $set;
    }

    /* ------------------------------------------------------------------ */
    /* Feature-line helpers                                                */
    /* ------------------------------------------------------------------ */

    /**
     * Split a full feature line into its bold numeric lead and the rest,
     * e.g. "10 GB NVMe storage" -> ["10 GB", "NVMe storage"],
     * "100 email accounts" -> ["100", "email accounts"],
     * "eCommerce" -> ["eCommerce", ""] (no numeric lead -> no split).
     *
     * @return array [lead, rest]
     */
    public static function leadAndRest($line)
    {
        $line = trim((string) $line);

        if (preg_match('/^(\d[\d.,]*(?:\s*(?:GB|MB|TB|Mbps|Core|Cores))?)(?:\s+(.*))?$/iu', $line, $m)) {
            return [trim($m[1]), isset($m[2]) ? trim($m[2]) : ''];
        }

        return [$line, ''];
    }

    /* ------------------------------------------------------------------ */
    /* Fallback: the content hardcoded in HostingController before the     */
    /* CMS, normalized with the same available/feature_key keys as DB rows.*/
    /* ------------------------------------------------------------------ */

    private function legacy($type)
    {
        if ($type === HostingPlan::TYPE_VPS) {
            return $this->legacyVps();
        }

        // Shared and dedicated always carried identical card data;
        // dedicated's page JS never enabled ecommerce, so it stays hidden.
        $ecommercePlan = $type === HostingPlan::TYPE_SHARED
            ? [false, false, true, true, true]   // A B C D E
            : [false, false, false, false, false];

        $primaryNames = ['NVMe storage', 'bandwidth/month', 'FTP users', 'websites/addons', 'email accounts'];

        $defs = [
            ['Plan A', 'plan-circle-basic', 'For small websites.', 'Cheapest', [300, 3000, 6000, 9000],
                [['10 GB', '10 GB'], ['300 GB', '100 GB'], ['100', '50'], ['3', '3'], ['100', '50']],
                [['100 email mailing lists', '50 email mailing lists'], ['10 MySQL databases', '10 MySQL databases'], ['100 subdomains', '50 subdomains']]],
            ['Plan B', 'plan-circle-standard', 'For growing websites.', null, [500, 5000, 10000, 15000],
                [['20 GB', '20 GB'], ['500 GB', '200 GB'], ['150', '100'], ['5', '5'], ['200', '100']],
                [['200 email mailing lists', '100 email mailing lists'], ['50 MySQL databases', '20 MySQL databases'], ['100 subdomains', '100 subdomains']]],
            ['Plan C', 'plan-circle-advanced', 'For busy websites.', 'Most popular', [1000, 10000, 20000, 30000],
                [['50 GB', '50 GB'], ['700 GB', '500 GB'], ['150', '100'], ['10', '10'], ['300', '100']],
                [['200 email mailing lists', '100 email mailing lists'], ['100 MySQL databases', '50 MySQL databases'], ['100 subdomains', '100 subdomains']]],
            ['Plan D', 'plan-circle-premium', 'For high-traffic websites.', null, [1500, 15000, 30000, 45000],
                [['100 GB', '100 GB'], ['1000 GB', '800 GB'], ['200', '100'], ['50', '20'], ['400', '100']],
                [['300 email mailing lists', '100 email mailing lists'], ['100 MySQL databases', '50 MySQL databases'], ['100 subdomains', '100 subdomains']]],
            ['Plan E', 'plan-circle-enterprise', 'For demanding businesses.', null, [2500, 25000, 50000, 75000],
                [['200 GB', '200 GB'], ['2000 GB', '1000 GB'], ['200', '100'], ['100', '50'], ['500', '200']],
                [['300 email mailing lists', '200 email mailing lists'], ['200 MySQL databases', '100 MySQL databases'], ['100 subdomains', '100 subdomains']]],
        ];

        $plans = [];
        foreach ($defs as $i => $def) {
            $ecommerceAvailable = $ecommercePlan[$i];

            $primary = [];
            foreach ($def[5] as $index => $value) {
                $primary[] = [
                    'monthly' => $value[0],
                    'yearly' => $value[1],
                    'name' => $primaryNames[$index],
                    'available' => true,
                    'feature_key' => null,
                ];
            }

            $extra = [];
            foreach ($def[6] as $value) {
                $extra[] = [
                    'monthly' => $value[0],
                    'yearly' => $value[1],
                    'name' => $value[0],
                    'available' => true,
                    'feature_key' => null,
                ];
            }

            // Ecommerce (data-feature driven on the shared page).
            $extra[] = [
                'monthly' => $ecommerceAvailable ? 'E-commerce' : 'eCommerce',
                'yearly' => $ecommerceAvailable ? 'E-commerce' : 'eCommerce',
                'name' => 'eCommerce',
                'available' => $ecommerceAvailable,
                'feature_key' => 'ecommerce',
            ];

            // The rest of the old $unavailableFeatures() list.
            foreach (['Remote database access', 'PostgreSQL databases', 'PhpPG Admin', 'SSH access (Terminal)', 'Shell access (Jailed)', 'eAccelerator'] as $name) {
                $extra[] = [
                    'monthly' => $name,
                    'yearly' => $name,
                    'name' => $name,
                    'available' => false,
                    'feature_key' => null,
                ];
            }

            $plans[] = [
                'name' => $def[0],
                'names' => ['monthly' => $def[0], 'yearly' => $def[0], 'twoYear' => $def[0], 'threeYear' => $def[0]],
                'class' => $def[1],
                'description' => $def[2],
                'badge' => $def[3],
                'prices' => ['monthly' => $def[4][0], 'yearly' => $def[4][1], 'twoYear' => $def[4][2], 'threeYear' => $def[4][3]],
                'terms' => ['monthly' => true, 'yearly' => true, 'twoYear' => false, 'threeYear' => false],
                'visibility' => ['monthly' => true, 'yearly' => true, 'twoYear' => true, 'threeYear' => true],
                'primary_features' => $primary,
                'extra_features' => $extra,
                // Un-edited in the CMS: twoYear/threeYear keep showing the
                // yearly values, exactly like the pre-CMS page JS did.
                'feature_sets' => $this->legacyFeatureSets($primary, $extra),
            ];
        }

        return $plans;
    }

    /** Feature sets for the hardcoded fallback content (monthly/yearly differ). */
    private function legacyFeatureSets($primary, $extra)
    {
        $makeSet = function ($valueKey) use ($primary, $extra) {
            $set = ['primary' => [], 'extra' => []];
            foreach ($primary as $item) {
                // The legacy primary rows store the lead ("300 GB") and the
                // name ("bandwidth/month") separately; the CMS stores one
                // full line — rebuild it so the split yields the same parts.
                $line = trim((string) $item[$valueKey] . ' ' . (string) $item['name']);
                [$lead, $rest] = self::leadAndRest($line);
                $set['primary'][] = [
                    'lead' => ($lead !== '' && $rest !== '') ? $lead : $line,
                    'name' => ($lead !== '' && $rest !== '') ? $rest : '',
                    'available' => (bool) $item['available'],
                    'feature_key' => $item['feature_key'],
                ];
            }
            foreach ($extra as $item) {
                $set['extra'][] = [
                    'text' => (string) $item[$valueKey],
                    'available' => (bool) $item['available'],
                    'feature_key' => $item['feature_key'],
                ];
            }

            return $set;
        };

        // Un-edited in the CMS: twoYear/threeYear keep showing the yearly
        // values, exactly like the pre-CMS page JS did.
        $yearly = $makeSet('yearly');

        return [
            'monthly' => $makeSet('monthly'),
            'yearly' => $yearly,
            'twoYear' => $yearly,
            'threeYear' => $yearly,
        ];
    }

    private function legacyVps()
    {
        $extraValues = ['200 GB IX bandwidth', '1 Dedicated IP', 'Root access', 'Instant activation', 'Server location: Bangladesh', 'Auto back-up system'];
        $primaryNames = ['CPU', 'RAM', 'NVMe storage', 'transactional bandwidth', 'internet bandwidth'];

        $defs = [
            ['Plan A', 'plan-circle-basic', 'A reliable start for personal sites and small projects.', 'Cheapest', [2000, 20000, 40000, 60000],
                [['2 Core', '2 Core'], ['4 GB', '4 GB'], ['40 GB', '40 GB'], ['200 GB', '200 GB'], ['20 Mbps', '20 Mbps']]],
            ['Plan B', 'plan-circle-standard', 'More room for growing websites, stores, and applications.', null, [3000, 30000, 60000, 90000],
                [['4 Core', '4 Core'], ['8 GB', '8 GB'], ['80 GB', '80 GB'], ['200 GB', '200 GB'], ['50 Mbps', '50 Mbps']]],
            ['Plan C', 'plan-circle-advanced', 'Extra power for busy, high-traffic workloads and teams.', 'Most popular', [4000, 40000, 80000, 120000],
                [['8 Core', '8 Core'], ['16 GB', '16 GB'], ['100 GB', '100 GB'], ['300 GB', '300 GB'], ['100 Mbps', '100 Mbps']]],
            ['Plan D', 'plan-circle-premium', 'Serious capacity for demanding business workloads.', null, [5000, 50000, 100000, 150000],
                [['16 Core', '16 Core'], ['32 GB', '32 GB'], ['150 GB', '150 GB'], ['500 GB', '500 GB'], ['200 Mbps', '200 Mbps']]],
        ];

        $plans = [];
        foreach ($defs as $def) {
            $primary = [];
            $extra = [];

            foreach ($def[5] as $index => $value) {
                $primary[] = [
                    'monthly' => $value[0],
                    'yearly' => $value[1],
                    'name' => $primaryNames[$index],
                    'available' => true,
                    'feature_key' => null,
                ];
            }

            foreach ($extraValues as $text) {
                $extra[] = [
                    'monthly' => $text,
                    'yearly' => $text,
                    'name' => $text,
                    'available' => true,
                    'feature_key' => null,
                ];
            }

            $plans[] = [
                'name' => $def[0],
                'names' => ['monthly' => $def[0], 'yearly' => $def[0], 'twoYear' => $def[0], 'threeYear' => $def[0]],
                'class' => $def[1],
                'description' => $def[2],
                'badge' => $def[3],
                'prices' => ['monthly' => $def[4][0], 'yearly' => $def[4][1], 'twoYear' => $def[4][2], 'threeYear' => $def[4][3]],
                'terms' => ['monthly' => true, 'yearly' => true, 'twoYear' => false, 'threeYear' => false],
                'visibility' => ['monthly' => true, 'yearly' => true, 'twoYear' => true, 'threeYear' => true],
                'primary_features' => $primary,
                'extra_features' => $extra,
                // Un-edited in the CMS: twoYear/threeYear keep showing the
                // yearly values, exactly like the pre-CMS page JS did.
                'feature_sets' => $this->legacyFeatureSets($primary, $extra),
            ];
        }

        return $plans;
    }
}
