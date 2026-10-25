<?php

use App\Models\HostingPlan;
use App\Models\HostingPlanFeature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * CMS-driven hosting plans.
 *
 * The plan cards on /hosting/shared, /hosting/vps, /hosting/dedicated and
 * the client-area compare page (/store/hosting/{type}) used to be hardcoded
 * arrays in HostingController. They now live in these two tables and are
 * edited from /admin/hosting-plans: prices, features, per-plan per-feature
 * available/unavailable flags, billing terms, card add/remove/reorder.
 *
 * This migration ALSO seeds the exact plan data that shipped hardcoded, and
 * it is guarded (seeds only when the table is empty), so the senior can
 * simply run `php artisan migrate` — no other deploy step is needed.
 * App\Services\HostingPlanService falls back to the historical arrays when
 * the tables are missing or empty, so the site renders even before this
 * migration runs.
 *
 * No real foreign keys, matching the rest of this schema.
 */
class CreateHostingPlans extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hosting_plans')) {
            Schema::create('hosting_plans', function ($table) {
                $table->increments('id');
                $table->string('type', 20)->index();               // shared | vps | dedicated
                $table->string('name', 120);
                $table->string('description')->nullable();
                $table->string('badge', 60)->nullable();           // "Cheapest", "Most popular", ...
                $table->string('style_class', 40)->default('plan-circle-basic');
                $table->decimal('price_monthly', 12, 2)->nullable();
                $table->decimal('price_yearly', 12, 2)->nullable();
                $table->decimal('price_two_year', 12, 2)->nullable();
                $table->decimal('price_three_year', 12, 2)->nullable();
                $table->boolean('term_monthly_active')->default(true);
                $table->boolean('term_yearly_active')->default(true);
                $table->boolean('term_two_year_active')->default(false);   // false renders "Coming soon"
                $table->boolean('term_three_year_active')->default(false);
                $table->boolean('is_visible')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hosting_plan_features')) {
            Schema::create('hosting_plan_features', function ($table) {
                $table->increments('id');
                $table->unsignedBigInteger('hosting_plan_id')->index();   // no FK (project convention)
                $table->string('feature_group', 10)->default('primary');  // primary | extra
                $table->string('name', 120);
                $table->string('value_monthly', 190);
                $table->string('value_yearly', 190)->nullable();
                $table->string('feature_key', 40)->nullable();            // data-feature="ecommerce" for the period-toggle JS
                $table->boolean('is_available')->default(true);           // false renders the red cross (feature-unavailable)
                $table->timestamps();
            });
        }

        $this->seed();
    }

    public function down()
    {
        Schema::dropIfExists('hosting_plan_features');
        Schema::dropIfExists('hosting_plans');
    }

    /** Insert today's hardcoded plans — only when the table is still empty. */
    private function seed()
    {
        if (HostingPlan::query()->exists()) {
            return;
        }

        foreach ($this->seedData() as $type => $plans) {
            foreach ($plans as $index => $plan) {
                /** @var HostingPlan $row */
                $row = HostingPlan::create([
                    'type' => $type,
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'badge' => $plan['badge'],
                    'style_class' => $plan['class'],
                    'price_monthly' => $plan['prices']['monthly'],
                    'price_yearly' => $plan['prices']['yearly'],
                    'price_two_year' => $plan['prices']['twoYear'],
                    'price_three_year' => $plan['prices']['threeYear'],
                    'term_monthly_active' => true,
                    'term_yearly_active' => true,
                    'term_two_year_active' => false,
                    'term_three_year_active' => false,
                    'is_visible' => true,
                    'sort' => $index + 1,
                ]);

                foreach ($plan['features'] as $feature) {
                    HostingPlanFeature::create([
                        'hosting_plan_id' => $row->id,
                        'feature_group' => $feature['group'],
                        'name' => $feature['name'],
                        'value_monthly' => $feature['monthly'],
                        'value_yearly' => $feature['yearly'] ?? $feature['monthly'],
                        'feature_key' => $feature['key'] ?? null,
                        'is_available' => $feature['available'] ?? true,
                    ]);
                }
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Seed data: the exact content that used to be hardcoded in           */
    /* HostingController. Feature rows preserve the current rendering:     */
    /*   - extra "available" rows show their monthly value with the icon   */
    /*   - "unavailable" rows render the red cross (feature-unavailable)   */
    /*   - ecommerce is available in shared Plans C/D/E (today's JS made   */
    /*     it so on /hosting/shared only) and unavailable everywhere else  */
    /*   - billing terms: monthly + yearly active, 2/3-year "Coming soon"  */
    /* ------------------------------------------------------------------ */

    private function seedData()
    {
        return [
            'shared' => [
                $this->sharedPlan('Plan A', 'plan-circle-basic', 'For small websites.', 'Cheapest',
                    [300, 3000, 6000, 9000],
                    [['10 GB', '10 GB'], ['300 GB', '100 GB'], ['100', '50'], ['3', '3'], ['100', '50']],
                    ['100 email mailing lists' => '50 email mailing lists', '10 MySQL databases' => '10 MySQL databases', '100 subdomains' => '50 subdomains'],
                    false),
                $this->sharedPlan('Plan B', 'plan-circle-standard', 'For growing websites.', null,
                    [500, 5000, 10000, 15000],
                    [['20 GB', '20 GB'], ['500 GB', '200 GB'], ['150', '100'], ['5', '5'], ['200', '100']],
                    ['200 email mailing lists' => '100 email mailing lists', '50 MySQL databases' => '20 MySQL databases', '100 subdomains' => '100 subdomains'],
                    false),
                $this->sharedPlan('Plan C', 'plan-circle-advanced', 'For busy websites.', 'Most popular',
                    [1000, 10000, 20000, 30000],
                    [['50 GB', '50 GB'], ['700 GB', '500 GB'], ['150', '100'], ['10', '10'], ['300', '100']],
                    ['200 email mailing lists' => '100 email mailing lists', '100 MySQL databases' => '50 MySQL databases', '100 subdomains' => '100 subdomains'],
                    true),
                $this->sharedPlan('Plan D', 'plan-circle-premium', 'For high-traffic websites.', null,
                    [1500, 15000, 30000, 45000],
                    [['100 GB', '100 GB'], ['1000 GB', '800 GB'], ['200', '100'], ['50', '20'], ['400', '100']],
                    ['300 email mailing lists' => '100 email mailing lists', '100 MySQL databases' => '50 MySQL databases', '100 subdomains' => '100 subdomains'],
                    true),
                $this->sharedPlan('Plan E', 'plan-circle-enterprise', 'For demanding businesses.', null,
                    [2500, 25000, 50000, 75000],
                    [['200 GB', '200 GB'], ['2000 GB', '1000 GB'], ['200', '100'], ['100', '50'], ['500', '200']],
                    ['300 email mailing lists' => '200 email mailing lists', '200 MySQL databases' => '100 MySQL databases', '100 subdomains' => '100 subdomains'],
                    true),
            ],

            // The dedicated page reuses the shared plan data, but its page JS
            // never enabled ecommerce — so it seeds with ecommerce unavailable.
            'dedicated' => [
                $this->sharedPlan('Plan A', 'plan-circle-basic', 'For small websites.', 'Cheapest',
                    [300, 3000, 6000, 9000],
                    [['10 GB', '10 GB'], ['300 GB', '100 GB'], ['100', '50'], ['3', '3'], ['100', '50']],
                    ['100 email mailing lists' => '50 email mailing lists', '10 MySQL databases' => '10 MySQL databases', '100 subdomains' => '50 subdomains'],
                    false),
                $this->sharedPlan('Plan B', 'plan-circle-standard', 'For growing websites.', null,
                    [500, 5000, 10000, 15000],
                    [['20 GB', '20 GB'], ['500 GB', '200 GB'], ['150', '100'], ['5', '5'], ['200', '100']],
                    ['200 email mailing lists' => '100 email mailing lists', '50 MySQL databases' => '20 MySQL databases', '100 subdomains' => '100 subdomains'],
                    false),
                $this->sharedPlan('Plan C', 'plan-circle-advanced', 'For busy websites.', 'Most popular',
                    [1000, 10000, 20000, 30000],
                    [['50 GB', '50 GB'], ['700 GB', '500 GB'], ['150', '100'], ['10', '10'], ['300', '100']],
                    ['200 email mailing lists' => '100 email mailing lists', '100 MySQL databases' => '50 MySQL databases', '100 subdomains' => '100 subdomains'],
                    false),
                $this->sharedPlan('Plan D', 'plan-circle-premium', 'For high-traffic websites.', null,
                    [1500, 15000, 30000, 45000],
                    [['100 GB', '100 GB'], ['1000 GB', '800 GB'], ['200', '100'], ['50', '20'], ['400', '100']],
                    ['300 email mailing lists' => '100 email mailing lists', '100 MySQL databases' => '50 MySQL databases', '100 subdomains' => '100 subdomains'],
                    false),
                $this->sharedPlan('Plan E', 'plan-circle-enterprise', 'For demanding businesses.', null,
                    [2500, 25000, 50000, 75000],
                    [['200 GB', '200 GB'], ['2000 GB', '1000 GB'], ['200', '100'], ['100', '50'], ['500', '200']],
                    ['300 email mailing lists' => '200 email mailing lists', '200 MySQL databases' => '100 MySQL databases', '100 subdomains' => '100 subdomains'],
                    false),
            ],

            'vps' => [
                $this->vpsPlan('Plan A', 'plan-circle-basic', 'A reliable start for personal sites and small projects.', 'Cheapest',
                    [2000, 20000, 40000, 60000],
                    [['2 Core', '2 Core'], ['4 GB', '4 GB'], ['40 GB', '40 GB'], ['200 GB', '200 GB'], ['20 Mbps', '20 Mbps']]),
                $this->vpsPlan('Plan B', 'plan-circle-standard', 'More room for growing websites, stores, and applications.', null,
                    [3000, 30000, 60000, 90000],
                    [['4 Core', '4 Core'], ['8 GB', '8 GB'], ['80 GB', '80 GB'], ['200 GB', '200 GB'], ['50 Mbps', '50 Mbps']]),
                $this->vpsPlan('Plan C', 'plan-circle-advanced', 'Extra power for busy, high-traffic workloads and teams.', 'Most popular',
                    [4000, 40000, 80000, 120000],
                    [['8 Core', '8 Core'], ['16 GB', '16 GB'], ['100 GB', '100 GB'], ['300 GB', '300 GB'], ['100 Mbps', '100 Mbps']]),
                $this->vpsPlan('Plan D', 'plan-circle-premium', 'Serious capacity for demanding business workloads.', null,
                    [5000, 50000, 100000, 150000],
                    [['16 Core', '16 Core'], ['32 GB', '32 GB'], ['150 GB', '150 GB'], ['500 GB', '500 GB'], ['200 Mbps', '200 Mbps']]),
            ],
        ];
    }

    /** One shared/dedicated plan: 5 primary + 3 extra + 7 "unavailable" rows.
     *  Primary features are stored as ONE full line ("10 GB NVMe storage")
     *  — the admin edits the whole line in a single field. */
    private function sharedPlan($name, $class, $description, $badge, $prices, $primary, $extras, $ecommerceAvailable)
    {
        $features = [];

        foreach ($primary as $i => $value) {
            $primaryName = ['NVMe storage', 'bandwidth/month', 'FTP users', 'websites/addons', 'email accounts'][$i];
            $monthly = trim($value[0] . ' ' . $primaryName);
            $yearly = trim($value[1] . ' ' . $primaryName);
            $features[] = [
                'group' => 'primary',
                'name' => $monthly,
                'monthly' => $monthly,
                'yearly' => $yearly,
                'available' => true,
            ];
        }

        foreach ($extras as $monthly => $yearly) {
            $features[] = [
                'group' => 'extra',
                'name' => $monthly,
                'monthly' => $monthly,
                'yearly' => $yearly,
                'available' => true,
            ];
        }

        $features[] = [
            'group' => 'extra',
            'name' => 'eCommerce',
            'monthly' => $ecommerceAvailable ? 'E-commerce' : 'eCommerce',
            'key' => 'ecommerce',
            'available' => $ecommerceAvailable,
        ];

        foreach (['Remote database access', 'PostgreSQL databases', 'PhpPG Admin', 'SSH access (Terminal)', 'Shell access (Jailed)', 'eAccelerator'] as $row) {
            $features[] = [
                'group' => 'extra',
                'name' => $row,
                'monthly' => $row,
                'available' => false,
            ];
        }

        return [
            'name' => $name,
            'description' => $description,
            'badge' => $badge,
            'class' => $class,
            'prices' => ['monthly' => $prices[0], 'yearly' => $prices[1], 'twoYear' => $prices[2], 'threeYear' => $prices[3]],
            'features' => $features,
        ];
    }

    /**
     * VPS plan: 5 primary + 6 extra features, every row available.
     * The 6 extras are identical for all 4 VPS plans (today's $extra array).
     */
    private function vpsPlan($name, $class, $description, $badge, $prices, $primary)
    {
        $features = [];

        foreach ($primary as $i => $value) {
            $primaryName = ['CPU', 'RAM', 'NVMe storage', 'transactional bandwidth', 'internet bandwidth'][$i];
            $monthly = trim($value[0] . ' ' . $primaryName);
            $yearly = trim($value[1] . ' ' . $primaryName);
            $features[] = [
                'group' => 'primary',
                'name' => $monthly,
                'monthly' => $monthly,
                'yearly' => $yearly,
                'available' => true,
            ];
        }

        foreach (['200 GB IX bandwidth', '1 Dedicated IP', 'Root access', 'Instant activation', 'Server location: Bangladesh', 'Auto back-up system'] as $row) {
            $features[] = [
                'group' => 'extra',
                'name' => $row,
                'monthly' => $row,
                'available' => true,
            ];
        }

        return [
            'name' => $name,
            'description' => $description,
            'badge' => $badge,
            'class' => $class,
            'prices' => ['monthly' => $prices[0], 'yearly' => $prices[1], 'twoYear' => $prices[2], 'threeYear' => $prices[3]],
            'features' => $features,
        ];
    }
}
