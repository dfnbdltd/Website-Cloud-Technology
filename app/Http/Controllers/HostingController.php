<?php

namespace App\Http\Controllers;

class HostingController extends Controller
{
    public function shared()
    {
        return view('hosting.shared', [
            'plans' => $this->sharedPlans(),
            'unavailableFeatures' => $this->unavailableFeatures(),
        ]);
    }

    public function vps()
    {
        return view('hosting.vps', [
            'plans' => $this->vpsPlans(),
        ]);
    }

    public function dedicated()
    {
        return view('hosting.dedicated', [
            'plans' => $this->dedicatedPlans(),
            'unavailableFeatures' => $this->unavailableFeatures(),
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
                'plans' => $this->sharedPlans(),
                'unavailableFeatures' => $this->unavailableFeatures(),
                'active' => 'hosting-shared',
            ],
            'vps' => [
                'title' => 'VPS Hosting',
                'kicker' => 'HOSTING PACKAGE',
                'subtitle' => 'Dedicated resources with root access — pick a plan and buy.',
                'plans' => $this->vpsPlans(),
                'unavailableFeatures' => [],
                'active' => 'hosting-vps',
            ],
            'dedicated' => [
                'title' => 'Dedicated Hosting',
                'kicker' => 'HOSTING PACKAGE',
                'subtitle' => 'Maximum power on your own server — pick a plan and buy.',
                'plans' => $this->dedicatedPlans(),
                'unavailableFeatures' => $this->unavailableFeatures(),
                'active' => 'hosting-dedicated',
            ],
        ];

        if (!isset($catalog[$type])) {
            abort(404);
        }

        return view('hosting.client', array_merge($catalog[$type], ['type' => $type]));
    }

    private function unavailableFeatures()
    {
        return [
            ['name' => 'eCommerce', 'feature' => 'ecommerce'],
            ['name' => 'Remote database access'],
            ['name' => 'PostgreSQL databases'],
            ['name' => 'PhpPG Admin'],
            ['name' => 'SSH access (Terminal)'],
            ['name' => 'Shell access (Jailed)'],
            ['name' => 'eAccelerator'],
        ];
    }

    private function sharedPlans()
    {
        return [
            [
                'name' => 'Plan A',
                'class' => 'plan-circle-basic',
                'description' => 'For small websites.',
                'badge' => 'Cheapest',
                'prices' => ['monthly' => 300, 'yearly' => 3000, 'twoYear' => 6000, 'threeYear' => 9000],
                'primary_features' => [
                    ['monthly' => '10 GB', 'yearly' => '10 GB', 'name' => 'NVMe storage'],
                    ['monthly' => '300 GB', 'yearly' => '100 GB', 'name' => 'bandwidth/month'],
                    ['monthly' => '100', 'yearly' => '50', 'name' => 'FTP users'],
                    ['monthly' => '3', 'yearly' => '3', 'name' => 'websites/addons'],
                    ['monthly' => '100', 'yearly' => '50', 'name' => 'email accounts'],
                ],
                'extra_features' => [
                    ['monthly' => '100 email mailing lists', 'yearly' => '50 email mailing lists'],
                    ['monthly' => '10 MySQL databases', 'yearly' => '10 MySQL databases'],
                    ['monthly' => '100 subdomains', 'yearly' => '50 subdomains'],
                ],
            ],
            [
                'name' => 'Plan B',
                'class' => 'plan-circle-standard',
                'description' => 'For growing websites.',
                'badge' => null,
                'prices' => ['monthly' => 500, 'yearly' => 5000, 'twoYear' => 10000, 'threeYear' => 15000],
                'primary_features' => [
                    ['monthly' => '20 GB', 'yearly' => '20 GB', 'name' => 'NVMe storage'],
                    ['monthly' => '500 GB', 'yearly' => '200 GB', 'name' => 'bandwidth/month'],
                    ['monthly' => '150', 'yearly' => '100', 'name' => 'FTP users'],
                    ['monthly' => '5', 'yearly' => '5', 'name' => 'websites/addons'],
                    ['monthly' => '200', 'yearly' => '100', 'name' => 'email accounts'],
                ],
                'extra_features' => [
                    ['monthly' => '200 email mailing lists', 'yearly' => '100 email mailing lists'],
                    ['monthly' => '50 MySQL databases', 'yearly' => '20 MySQL databases'],
                    ['monthly' => '100 subdomains', 'yearly' => '100 subdomains'],
                ],
            ],
            [
                'name' => 'Plan C',
                'class' => 'plan-circle-advanced',
                'description' => 'For busy websites.',
                'badge' => 'Most popular',
                'prices' => ['monthly' => 1000, 'yearly' => 10000, 'twoYear' => 20000, 'threeYear' => 30000],
                'primary_features' => [
                    ['monthly' => '50 GB', 'yearly' => '50 GB', 'name' => 'NVMe storage'],
                    ['monthly' => '700 GB', 'yearly' => '500 GB', 'name' => 'bandwidth/month'],
                    ['monthly' => '150', 'yearly' => '100', 'name' => 'FTP users'],
                    ['monthly' => '10', 'yearly' => '10', 'name' => 'websites/addons'],
                    ['monthly' => '300', 'yearly' => '100', 'name' => 'email accounts'],
                ],
                'extra_features' => [
                    ['monthly' => '200 email mailing lists', 'yearly' => '100 email mailing lists'],
                    ['monthly' => '100 MySQL databases', 'yearly' => '50 MySQL databases'],
                    ['monthly' => '100 subdomains', 'yearly' => '100 subdomains'],
                ],
            ],
            [
                'name' => 'Plan D',
                'class' => 'plan-circle-premium',
                'description' => 'For high-traffic websites.',
                'badge' => null,
                'prices' => ['monthly' => 1500, 'yearly' => 15000, 'twoYear' => 30000, 'threeYear' => 45000],
                'primary_features' => [
                    ['monthly' => '100 GB', 'yearly' => '100 GB', 'name' => 'NVMe storage'],
                    ['monthly' => '1000 GB', 'yearly' => '800 GB', 'name' => 'bandwidth/month'],
                    ['monthly' => '200', 'yearly' => '100', 'name' => 'FTP users'],
                    ['monthly' => '50', 'yearly' => '20', 'name' => 'websites/addons'],
                    ['monthly' => '400', 'yearly' => '100', 'name' => 'email accounts'],
                ],
                'extra_features' => [
                    ['monthly' => '300 email mailing lists', 'yearly' => '100 email mailing lists'],
                    ['monthly' => '100 MySQL databases', 'yearly' => '50 MySQL databases'],
                    ['monthly' => '100 subdomains', 'yearly' => '100 subdomains'],
                ],
            ],
            [
                'name' => 'Plan E',
                'class' => 'plan-circle-enterprise',
                'description' => 'For demanding businesses.',
                'badge' => null,
                'prices' => ['monthly' => 2500, 'yearly' => 25000, 'twoYear' => 50000, 'threeYear' => 75000],
                'primary_features' => [
                    ['monthly' => '200 GB', 'yearly' => '200 GB', 'name' => 'NVMe storage'],
                    ['monthly' => '2000 GB', 'yearly' => '1000 GB', 'name' => 'bandwidth/month'],
                    ['monthly' => '200', 'yearly' => '100', 'name' => 'FTP users'],
                    ['monthly' => '100', 'yearly' => '50', 'name' => 'websites/addons'],
                    ['monthly' => '500', 'yearly' => '200', 'name' => 'email accounts'],
                ],
                'extra_features' => [
                    ['monthly' => '300 email mailing lists', 'yearly' => '200 email mailing lists'],
                    ['monthly' => '200 MySQL databases', 'yearly' => '100 MySQL databases'],
                    ['monthly' => '100 subdomains', 'yearly' => '100 subdomains'],
                ],
            ],
        ];
    }

    /**
     * VPS plans extracted from vps.html (view data only).
     * Same shape as sharedPlans() so views stay MVC-driven.
     */
    private function vpsPlans()
    {
        $extra = [
            ['monthly' => '200 GB IX bandwidth', 'yearly' => '200 GB IX bandwidth'],
            ['monthly' => '1 Dedicated IP', 'yearly' => '1 Dedicated IP'],
            ['monthly' => 'Root access', 'yearly' => 'Root access'],
            ['monthly' => 'Instant activation', 'yearly' => 'Instant activation'],
            ['monthly' => 'Server location: Bangladesh', 'yearly' => 'Server location: Bangladesh'],
            ['monthly' => 'Auto back-up system', 'yearly' => 'Auto back-up system'],
        ];

        return [
            [
                'name' => 'Plan A',
                'class' => 'plan-circle-basic',
                'description' => 'A reliable start for personal sites and small projects.',
                'badge' => 'Cheapest',
                'prices' => ['monthly' => 2000, 'yearly' => 20000, 'twoYear' => 40000, 'threeYear' => 60000],
                'primary_features' => [
                    ['monthly' => '2 Core', 'yearly' => '2 Core', 'name' => 'CPU'],
                    ['monthly' => '4 GB', 'yearly' => '4 GB', 'name' => 'RAM'],
                    ['monthly' => '40 GB', 'yearly' => '40 GB', 'name' => 'NVMe storage'],
                    ['monthly' => '200 GB', 'yearly' => '200 GB', 'name' => 'transactional bandwidth'],
                    ['monthly' => '20 Mbps', 'yearly' => '20 Mbps', 'name' => 'internet bandwidth'],
                ],
                'extra_features' => $extra,
            ],
            [
                'name' => 'Plan B',
                'class' => 'plan-circle-standard',
                'description' => 'More room for growing websites, stores, and applications.',
                'badge' => null,
                'prices' => ['monthly' => 3000, 'yearly' => 30000, 'twoYear' => 60000, 'threeYear' => 90000],
                'primary_features' => [
                    ['monthly' => '4 Core', 'yearly' => '4 Core', 'name' => 'CPU'],
                    ['monthly' => '8 GB', 'yearly' => '8 GB', 'name' => 'RAM'],
                    ['monthly' => '80 GB', 'yearly' => '80 GB', 'name' => 'NVMe storage'],
                    ['monthly' => '200 GB', 'yearly' => '200 GB', 'name' => 'transactional bandwidth'],
                    ['monthly' => '50 Mbps', 'yearly' => '50 Mbps', 'name' => 'internet bandwidth'],
                ],
                'extra_features' => $extra,
            ],
            [
                'name' => 'Plan C',
                'class' => 'plan-circle-advanced',
                'description' => 'Extra power for busy, high-traffic workloads and teams.',
                'badge' => 'Most popular',
                'prices' => ['monthly' => 4000, 'yearly' => 40000, 'twoYear' => 80000, 'threeYear' => 120000],
                'primary_features' => [
                    ['monthly' => '8 Core', 'yearly' => '8 Core', 'name' => 'CPU'],
                    ['monthly' => '16 GB', 'yearly' => '16 GB', 'name' => 'RAM'],
                    ['monthly' => '100 GB', 'yearly' => '100 GB', 'name' => 'NVMe storage'],
                    ['monthly' => '300 GB', 'yearly' => '300 GB', 'name' => 'transactional bandwidth'],
                    ['monthly' => '100 Mbps', 'yearly' => '100 Mbps', 'name' => 'internet bandwidth'],
                ],
                'extra_features' => $extra,
            ],
            [
                'name' => 'Plan D',
                'class' => 'plan-circle-premium',
                'description' => 'Serious capacity for demanding business workloads.',
                'badge' => null,
                'prices' => ['monthly' => 5000, 'yearly' => 50000, 'twoYear' => 100000, 'threeYear' => 150000],
                'primary_features' => [
                    ['monthly' => '16 Core', 'yearly' => '16 Core', 'name' => 'CPU'],
                    ['monthly' => '32 GB', 'yearly' => '32 GB', 'name' => 'RAM'],
                    ['monthly' => '150 GB', 'yearly' => '150 GB', 'name' => 'NVMe storage'],
                    ['monthly' => '500 GB', 'yearly' => '500 GB', 'name' => 'transactional bandwidth'],
                    ['monthly' => '200 Mbps', 'yearly' => '200 Mbps', 'name' => 'internet bandwidth'],
                ],
                'extra_features' => $extra,
            ],
        ];
    }

    /**
     * Dedicated plans extracted from dedicated.html (view data only).
     * Same shape as sharedPlans().
     */
    private function dedicatedPlans()
    {
        return $this->sharedPlans();
    }
}
