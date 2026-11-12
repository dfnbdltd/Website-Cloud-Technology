<?php
/**
 * Throwaway: apply the three remaining test rewrites with real PHP strings, so
 * the regex backslashes never have to survive a JSON round trip.
 */
$root = dirname(__DIR__);
$path = $root . '/tests/Feature/SslPlanFeatureVisibilityTest.php';

$raw = file_get_contents($path);
$hadCrlf = strpos($raw, "\r\n") !== false;
$text = $hadCrlf ? str_replace("\r\n", "\n", $raw) : $raw;

$patches = [];

// --- 1. toggle-count test: count input names, not a class the JS reuses ------
$patches[] = [
    'old' => <<<'OLD'
    /** @test */
    public function the_admin_editor_renders_one_toggle_per_feature_with_the_right_state()
    {
        $plan = $this->positiveSsl();
        $plan->features()->orderBy('sort')->first()
            ->forceFill(['shown_to_customers' => false])->save();

        $response = $this->admin()->get("/admin/ssl-plans/{$plan->id}/edit")->assertOk();

        $html = $response->getContent();

        // One checkbox per stored feature.
        $count = $plan->fresh()->features()->count();
        $this->assertSame($count, substr_count($html, 'class="ssl-shown"'));

        // The hidden row carries no `checked` on its toggle, and is painted as
        // hidden so the state is visible in the list.
        $this->assertStringContainsString('is-hidden-feature', $html);
        $this->assertStringContainsString('name="features[0][shown]"', $html);

        // First row is the hidden one, so its checkbox must not be checked.
        preg_match('/name="features\[0\]\[shown\]"[^>]*/', $html, $m);
        $this->assertNotEmpty($m);
        $this->assertStringNotContainsString('checked', $m[0]);
    }
OLD,
    'new' => <<<'NEW'
    /** @test */
    public function the_admin_editor_renders_one_toggle_per_feature_with_the_right_state()
    {
        $plan = $this->positiveSsl();
        $plan->features()->orderBy('sort')->first()
            ->forceFill(['shown_to_customers' => false])->save();

        $response = $this->admin()->get("/admin/ssl-plans/{$plan->id}/edit")->assertOk();
        $html = $response->getContent();

        // One checkbox per stored feature, counted by input name: the
        // "ssl-shown" class also appears once in the inline JS that builds a row.
        $count = $plan->fresh()->features()->count();
        $this->assertSame($count, preg_match_all('/name="features\[\d+\]\[shown\]"/', $html));

        // The hidden row is painted as hidden, so the state is visible in a list.
        $this->assertStringContainsString('is-hidden-feature', $html);

        // First row is the hidden one, so its checkbox carries no `checked`.
        preg_match('/name="features\[0\]\[shown\]"[^>]*>/', $html, $first);
        $this->assertNotEmpty($first, 'the first feature row must render a visibility toggle');
        $this->assertStringNotContainsString('checked', $first[0]);

        // The second row is still shown, so it does.
        preg_match('/name="features\[1\]\[shown\]"[^>]*>/', $html, $second);
        $this->assertNotEmpty($second);
        $this->assertStringContainsString('checked', $second[0]);
    }
NEW,
];

// --- 2. whole-plan hide: use unique text so nothing else can match ----------
$patches[] = [
    'old' => <<<'OLD'
    /** @test */
    public function hiding_the_whole_plan_still_hides_it_regardless_of_its_features()
    {
        $plan = $this->positiveSsl();

        $this->assertStringContainsString('Positive SSL', $this->get('/services/ssl-certificate')->getContent());

        $this->admin()->post("/admin/ssl-plans/{$plan->id}/visibility")->assertRedirect('/admin/ssl-plans');

        $this->assertStringNotContainsString('Positive SSL', $this->get('/services/ssl-certificate')->getContent());

        // And showing it again restores the card with its features.
        $this->admin()->post("/admin/ssl-plans/{$plan->id}/visibility")->assertRedirect('/admin/ssl-plans');

        $this->get('/services/ssl-certificate')
            ->assertSee('Positive SSL')
            ->assertSee('Domain Validation');
    }
OLD,
    'new' => <<<'NEW'
    /** @test */
    public function hiding_the_whole_plan_still_hides_it_regardless_of_its_features()
    {
        $plan = $this->positiveSsl();

        // Give the plan a name and a feature that appear nowhere else, so the
        // assertions cannot match the navbar, the coming-soon dialog or a
        // neighbouring card's feature list.
        $plan->update(['name' => 'UNIQUE-PLAN-NAME']);
        $this->admin()->post("/admin/ssl-plans/{$plan->id}", $this->payload($plan->fresh(), [
            ['text' => 'UNIQUE-PLAN-FEATURE', 'shown' => '1'],
        ]))->assertRedirect('/admin/ssl-plans');

        $this->get('/services/ssl-certificate')
            ->assertSee('UNIQUE-PLAN-NAME')
            ->assertSee('UNIQUE-PLAN-FEATURE');

        // Hide the whole plan.
        $this->admin()->post("/admin/ssl-plans/{$plan->id}/visibility")->assertRedirect('/admin/ssl-plans');

        $this->get('/services/ssl-certificate')
            ->assertDontSee('UNIQUE-PLAN-NAME')
            ->assertDontSee('UNIQUE-PLAN-FEATURE');

        // Showing it again restores the card and its features.
        $this->admin()->post("/admin/ssl-plans/{$plan->id}/visibility")->assertRedirect('/admin/ssl-plans');

        $this->get('/services/ssl-certificate')
            ->assertSee('UNIQUE-PLAN-NAME')
            ->assertSee('UNIQUE-PLAN-FEATURE');
    }
NEW,
];

// --- 3. config fallback: reset the memoised schema check --------------------
$patches[] = [
    'old' => <<<'OLD'
        // No tables at all -> config/ssl_plans.php path.
        Schema::disableForeignKeyConstraints();
        Schema::drop('ssl_plan_features');
        Schema::drop('ssl_plans');

        $cards = app(\App\Services\SslPlanService::class)->plans();
        $this->assertNotEmpty($cards);

        foreach ($cards as $card) {
            foreach ($card['features'] as $feature) {
                $this->assertTrue($feature['shown'], 'fallback features must default to shown');
            }
        }
OLD,
    'new' => <<<'NEW'
        // Drop both SSL tables so SslPlanService takes the config/ssl_plans.php
        // path. The service memoises that schema check, so reset it or the
        // already-managed result would be reused.
        Schema::drop('ssl_plan_features');
        Schema::drop('ssl_plans');

        $property = (new \ReflectionClass(\App\Services\SslPlanService::class))->getProperty('tablesMissing');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $cards = app(\App\Services\SslPlanService::class)->plans();
        $this->assertNotEmpty($cards, 'the config fallback must still return plans');

        foreach ($cards as $card) {
            $this->assertNotEmpty($card['features']);
            foreach ($card['features'] as $feature) {
                $this->assertTrue($feature['shown'], 'fallback features must default to shown');
            }
        }
NEW,
];

$failures = 0;

foreach ($patches as $index => $patch) {
    $count = substr_count($text, $patch['old']);
    if ($count !== 1) {
        fwrite(STDERR, '[' . $index . '] expected 1 match, found ' . $count . "\n");
        $failures++;
        continue;
    }
    $text = str_replace($patch['old'], $patch['new'], $text);
    echo '[' . $index . "] OK\n";
}

if ($failures === 0) {
    file_put_contents($path, $hadCrlf ? str_replace("\n", "\r\n", $text) : $text);
    echo "written: " . strlen($text) . " bytes\n";
}

exit($failures === 0 ? 0 : 1);
