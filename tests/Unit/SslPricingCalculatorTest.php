<?php

namespace Tests\Unit;

use App\Services\ResellerClubClient;
use PHPUnit\Framework\TestCase;

/**
 * ResellerClubClient::buildSslPricing() — the pure price calculator behind live
 * SSL pricing.
 *
 * Uses the live account's real response shape (captured 2026-11-11), because
 * the interesting cases are in it: the API reports prices in USD *per month*,
 * tenures are month counts, the cost list carries a 24-month tenure that the
 * selling list does not, and ResellerClub's "profit %" is a markup on cost that
 * the API never returns.
 *
 * Deliberately extends PHPUnit's TestCase rather than the project's: no
 * framework, no database, no network.
 */
class SslPricingCalculatorTest extends TestCase
{
    /** Selling prices: `sslcert.plans` from /products/customer-price.json. */
    private function selling()
    {
        return [
            '299' => ['renew' => ['12' => 4], 'add' => ['12' => 4]],
            '300' => ['renew' => ['12' => 1.99], 'add' => ['12' => 0.79]],
            '301' => ['renew' => ['12' => 5.99], 'add' => ['12' => 5.99]],
            '672' => ['renew' => ['12' => 17, '24' => 17], 'add' => ['12' => 13, '24' => 10]],
        ];
    }

    /** Cost prices: `sslcert.plans` from /products/reseller-cost-price.json. */
    private function cost()
    {
        return [
            '299' => ['renew' => ['12' => 3.33, '24' => 3.33], 'add' => ['12' => 3.33, '24' => 3.33]],
            '300' => ['renew' => ['12' => 1.66, '24' => 1.66], 'add' => ['12' => 0.66, '24' => 1.16]],
            '301' => ['renew' => ['12' => 4.99, '24' => 4.99], 'add' => ['12' => 4.99, '24' => 4.99]],
            '672' => ['renew' => ['12' => 14.16, '24' => 14.16], 'add' => ['12' => 10.83, '24' => 8.33]],
        ];
    }

    private function prices()
    {
        return ResellerClubClient::buildSslPricing($this->selling(), $this->cost());
    }

    /** @test */
    public function premium_ssl_matches_the_resellerclub_panel()
    {
        // $3.33 cost -> $4.00 selling is the panel's 20.12%.
        $row = $this->prices()[299]['add'][12];

        $this->assertSame(3.33, $row['cost']);
        $this->assertSame(4.0, $row['selling']);
        $this->assertSame(0.67, $row['profit_amount']);
        $this->assertSame(20.12, $row['profit_percent']);
    }

    /** @test */
    public function standard_ssl_matches_the_resellerclub_panel()
    {
        $prices = $this->prices();

        $this->assertSame(0.66, $prices[300]['add'][12]['cost']);
        $this->assertSame(0.79, $prices[300]['add'][12]['selling']);
        $this->assertSame(0.13, $prices[300]['add'][12]['profit_amount']);
        $this->assertSame(19.7, $prices[300]['add'][12]['profit_percent']);

        // Renewal is priced separately.
        $this->assertSame(1.66, $prices[300]['renew'][12]['cost']);
        $this->assertSame(1.99, $prices[300]['renew'][12]['selling']);
        $this->assertSame(0.33, $prices[300]['renew'][12]['profit_amount']);
        $this->assertSame(19.88, $prices[300]['renew'][12]['profit_percent']);
    }

    /** @test */
    public function wildcard_ssl_matches_the_resellerclub_panel()
    {
        $row = $this->prices()[301]['add'][12];

        $this->assertSame(4.99, $row['cost']);
        $this->assertSame(5.99, $row['selling']);
        $this->assertSame(1.0, $row['profit_amount']);
        $this->assertSame(20.04, $row['profit_percent']);
    }

    /** @test */
    public function ev_ssl_matches_the_resellerclub_panel_for_both_tenures()
    {
        $prices = $this->prices();

        $this->assertSame(10.83, $prices[672]['add'][12]['cost']);
        $this->assertSame(13.0, $prices[672]['add'][12]['selling']);
        $this->assertSame(2.17, $prices[672]['add'][12]['profit_amount']);
        $this->assertSame(20.04, $prices[672]['add'][12]['profit_percent']);

        // The 24-month cost is LOWER than the 12-month cost (8.33 vs 10.83).
        $this->assertSame(8.33, $prices[672]['add'][24]['cost']);
        $this->assertSame(10.0, $prices[672]['add'][24]['selling']);
        $this->assertSame(1.67, $prices[672]['add'][24]['profit_amount']);
        $this->assertSame(20.05, $prices[672]['add'][24]['profit_percent']);

        $this->assertSame(14.16, $prices[672]['renew'][12]['cost']);
        $this->assertSame(17.0, $prices[672]['renew'][12]['selling']);
        $this->assertSame(20.06, $prices[672]['renew'][12]['profit_percent']);
    }

    /** @test */
    public function a_tenure_with_no_selling_price_reports_null_not_zero()
    {
        $prices = $this->prices();

        // 299/300/301 have a 24-month COST but no 24-month SELLING price,
        // because the owner has not added that tenure in the panel.
        foreach ([299 => 3.33, 300 => 1.16, 301 => 4.99] as $planId => $expectedCost) {
            $row = $prices[$planId]['add'][24];

            $this->assertSame($expectedCost, $row['cost'], 'plan ' . $planId . ' 24-month cost');
            $this->assertNull($row['selling'], 'plan ' . $planId . ' 24-month selling');
            $this->assertNull($row['profit_amount'], 'plan ' . $planId . ' 24-month profit amount');
            $this->assertNull($row['profit_percent'], 'plan ' . $planId . ' 24-month profit percent');
        }
    }

    /** @test */
    public function an_unknown_plan_id_returns_empty_lists_without_throwing()
    {
        $prices = ResellerClubClient::buildSslPricing($this->selling(), $this->cost(), [999]);

        $this->assertArrayHasKey(999, $prices);
        $this->assertSame(999, $prices[999]['plan_id']);
        $this->assertSame([], $prices[999]['add']);
        $this->assertSame([], $prices[999]['renew']);
    }

    /** @test */
    public function nulls_and_junk_produce_nulls_never_infinities_or_division_by_zero()
    {
        $prices = ResellerClubClient::buildSslPricing(
            [500 => ['add' => ['12' => '4.00']]],   // numeric string
            [500 => ['add' => ['12' => 0]],        // zero cost -> no profit %
             501 => ['add' => ['12' => 'n/a']]],   // non-numeric cost
            [500, 501]
        );

        $this->assertSame(4.0, $prices[500]['add'][12]['selling']);
        $this->assertNull($prices[500]['add'][12]['cost']);
        $this->assertNull($prices[500]['add'][12]['profit_amount']);
        $this->assertNull($prices[500]['add'][12]['profit_percent']);

        $this->assertNull($prices[501]['add'][12]['cost']);
        $this->assertNull($prices[501]['add'][12]['selling']);
        $this->assertNull($prices[501]['add'][12]['profit_percent']);
    }

    /** @test */
    public function empty_input_returns_an_empty_result()
    {
        $this->assertSame([], ResellerClubClient::buildSslPricing([], []));
    }

    /** @test */
    public function the_plan_id_filter_returns_only_the_plans_asked_for()
    {
        $prices = ResellerClubClient::buildSslPricing($this->selling(), $this->cost(), [300]);

        $this->assertSame([300], array_keys($prices));
    }

    /** @test */
    public function every_tenure_is_a_month_count_and_there_is_no_three_year_term()
    {
        foreach ($this->prices() as $plan) {
            foreach (['add', 'renew'] as $action) {
                foreach (array_keys($plan[$action]) as $months) {
                    $this->assertIsInt($months);
                    $this->assertContains($months, [12, 24], 'unexpected tenure: ' . $months);
                }
            }
        }
    }

    /** @test */
    public function selling_only_mode_never_invents_a_cost()
    {
        // What the customer-facing path builds: selling list, no cost list.
        $prices = ResellerClubClient::buildSslPricing($this->selling(), [], [300]);

        $this->assertSame(0.79, $prices[300]['add'][12]['selling']);
        $this->assertNull($prices[300]['add'][12]['cost']);
        $this->assertNull($prices[300]['add'][12]['profit_percent']);
    }
}
