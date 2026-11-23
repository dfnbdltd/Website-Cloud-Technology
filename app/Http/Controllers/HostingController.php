<?php

namespace App\Http\Controllers;

use App\Models\HostingPlan;
use App\Services\HostingPlanService;
use Illuminate\Http\Request;

/**
 * Public hosting pages.
 *
 *   Hosting
 *   ├── Local Hosting      /hosting/local/{shared|vps|dedicated}
 *   ├── USA Hosting        /hosting/usa/{shared|vps|dedicated}
 *   └── WordPress Hosting  /hosting/wordpress
 *
 * Every page reads its cards from HostingPlanService (the admin edits them
 * under /admin/hosting-plans). The old /hosting/shared, /hosting/vps and
 * /hosting/dedicated URLs still work: they redirect to the Local pages.
 */
class HostingController extends Controller
{
    /** @var HostingPlanService */
    private $plans;

    public function __construct(HostingPlanService $plans)
    {
        $this->plans = $plans;
    }

    /** Local / USA × Shared / VPS / Dedicated. */
    public function region($region, $type)
    {
        $section = HostingPlan::normalizeSection($region, $type);
        if ($section === null || $section[0] === HostingPlan::REGION_WORDPRESS) {
            abort(404);
        }
        [$region, $type] = $section;

        return view('hosting.' . $type, [
            'plans' => $this->plans->plans($type, $region),
            'terms' => $this->plans->terms($type, $region),
            'region' => $region,
            'regionLabel' => HostingPlan::REGION_SHORT[$region],
            'type' => $type,
        ]);
    }

    /** WordPress hosting: one simple list of cards. */
    public function wordpress()
    {
        $type = HostingPlan::TYPE_WORDPRESS;
        $region = HostingPlan::REGION_WORDPRESS;

        return view('hosting.wordpress', [
            'plans' => $this->plans->plans($type, $region),
            'terms' => $this->plans->terms($type, $region),
        ]);
    }

    /** Old URLs (/hosting/shared ...) -> the Local page of the same type. */
    public function legacyRedirect($type)
    {
        return redirect()->route('hosting.region', [
            'region' => HostingPlan::REGION_LOCAL,
            'type' => $type,
        ], 301);
    }

    /**
     * Client-area plan list (logged-in customers only).
     * View-only listing driven by the same plan data as the public pages.
     *
     *   /store/hosting/shared               Local shared plans
     *   /store/hosting/shared?region=usa    USA shared plans
     *   /store/hosting/wordpress            WordPress plans
     */
    public function clientPlans(Request $request, $type)
    {
        if ($type === HostingPlan::TYPE_WORDPRESS) {
            $region = HostingPlan::REGION_WORDPRESS;
        } else {
            $region = $request->query('region') === HostingPlan::REGION_USA
                ? HostingPlan::REGION_USA
                : HostingPlan::REGION_LOCAL;
        }

        $section = HostingPlan::normalizeSection($region, $type);
        if ($section === null) {
            abort(404);
        }
        [$region, $type] = $section;

        $copy = [
            'shared' => [
                'title' => 'Shared Hosting',
                'subtitle' => 'Pick a plan, choose billing, and buy — all inside your client area.',
                'active' => 'hosting-shared',
            ],
            'vps' => [
                'title' => 'VPS Hosting',
                'subtitle' => 'Dedicated resources with root access — pick a plan and buy.',
                'active' => 'hosting-vps',
            ],
            'dedicated' => [
                'title' => 'Dedicated Hosting',
                'subtitle' => 'Maximum power on your own server — pick a plan and buy.',
                'active' => 'hosting-dedicated',
            ],
            'wordpress' => [
                'title' => 'WordPress Hosting',
                'subtitle' => 'Hosting tuned for WordPress — pick a plan and buy.',
                'active' => 'hosting-wordpress',
            ],
        ][$type];

        $regionLabel = HostingPlan::REGION_SHORT[$region];

        return view('hosting.client', [
            'title' => $region === HostingPlan::REGION_WORDPRESS ? $copy['title'] : $regionLabel . ' ' . $copy['title'],
            'kicker' => 'HOSTING PACKAGE',
            'subtitle' => $copy['subtitle'],
            'plans' => $this->plans->plans($type, $region),
            'terms' => $this->plans->terms($type, $region),
            'active' => $copy['active'],
            'type' => $type,
            'region' => $region,
            'publicUrl' => $region === HostingPlan::REGION_WORDPRESS
                ? route('hosting.wordpress')
                : route('hosting.region', ['region' => $region, 'type' => $type]),
        ]);
    }
}
