<?php

namespace App\Http\Controllers;

use App\Services\HostingPlanService;

class HostingController extends Controller
{
    /** @var HostingPlanService */
    private $plans;

    public function __construct(HostingPlanService $plans)
    {
        $this->plans = $plans;
    }

    public function shared()
    {
        return view('hosting.shared', [
            'plans' => $this->plans->plans('shared'),
            'terms' => $this->plans->terms('shared'),
        ]);
    }

    public function vps()
    {
        return view('hosting.vps', [
            'plans' => $this->plans->plans('vps'),
            'terms' => $this->plans->terms('vps'),
        ]);
    }

    public function dedicated()
    {
        return view('hosting.dedicated', [
            'plans' => $this->plans->plans('dedicated'),
            'terms' => $this->plans->terms('dedicated'),
        ]);
    }

    /**
     * Client-area plan list (logged-in customers only).
     * View-only listing driven by the same plan data as the public pages.
     */
    public function clientPlans($type)
    {
        $catalog = [
            'shared' => [
                'title' => 'Shared Hosting',
                'kicker' => 'HOSTING PACKAGE',
                'subtitle' => 'Pick a plan, choose billing, and buy — all inside your client area.',
                'plans' => $this->plans->plans('shared'),
                'terms' => $this->plans->terms('shared'),
                'active' => 'hosting-shared',
            ],
            'vps' => [
                'title' => 'VPS Hosting',
                'kicker' => 'HOSTING PACKAGE',
                'subtitle' => 'Dedicated resources with root access — pick a plan and buy.',
                'plans' => $this->plans->plans('vps'),
                'terms' => $this->plans->terms('vps'),
                'active' => 'hosting-vps',
            ],
            'dedicated' => [
                'title' => 'Dedicated Hosting',
                'kicker' => 'HOSTING PACKAGE',
                'subtitle' => 'Maximum power on your own server — pick a plan and buy.',
                'plans' => $this->plans->plans('dedicated'),
                'terms' => $this->plans->terms('dedicated'),
                'active' => 'hosting-dedicated',
            ],
        ];

        if (!isset($catalog[$type])) {
            abort(404);
        }

        return view('hosting.client', array_merge($catalog[$type], ['type' => $type]));
    }
}
