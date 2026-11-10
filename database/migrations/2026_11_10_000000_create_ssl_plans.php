<?php

use App\Models\SslPlan;
use App\Models\SslPlanFeature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateSslPlans extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('ssl_plans')) {
            Schema::create('ssl_plans', function ($table) {
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
                $table->boolean('term_one_year_active')->default(true);
                $table->boolean('term_two_year_active')->default(true);
                $table->boolean('term_three_year_active')->default(true);
                $table->boolean('term_one_year_visible')->default(true);
                $table->boolean('term_two_year_visible')->default(true);
                $table->boolean('term_three_year_visible')->default(true);
                $table->boolean('is_visible')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ssl_plan_features')) {
            Schema::create('ssl_plan_features', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('ssl_plan_id')->index();
                $table->string('feature', 190);
                $table->boolean('is_available')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        $this->seed();
    }

    public function down()
    {
        Schema::dropIfExists('ssl_plan_features');
        Schema::dropIfExists('ssl_plans');
    }

    private function seed()
    {
        if (SslPlan::query()->exists()) {
            return;
        }

        $config = config('ssl_plans', []);
        $sort = 1;
        foreach ((array) ($config['plans'] ?? []) as $key => $plan) {
            $row = SslPlan::create([
                'plan_key' => (string) $key,
                'name' => $plan['name'],
                'tagline' => $plan['tagline'] ?? null,
                'badge' => $plan['badge'] ?? null,
                'monthly_price' => $plan['monthly'] ?? 0,
                'renewal_price' => $plan['renewal'] ?? $plan['monthly'] ?? 0,
                'discount_one_year' => $config['term_discount'][1] ?? 0,
                'discount_two_year' => $config['term_discount'][2] ?? 5,
                'discount_three_year' => $config['term_discount'][3] ?? 10,
                'sort' => $sort++,
            ]);

            foreach ((array) ($plan['features'] ?? []) as $featureIndex => $feature) {
                SslPlanFeature::create([
                    'ssl_plan_id' => $row->id,
                    'feature' => $feature,
                    'sort' => $featureIndex + 1,
                ]);
            }
        }
    }
}
