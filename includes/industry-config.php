<?php
/**
 * Industry vertical landing pages — /industries/{slug}
 */
require_once __DIR__ . '/locale.php';

const INDUSTRY_PAGES = [
    'fintech' => [
        'name'        => 'FinTech',
        'headline'    => 'FinTech Execution Partner for Scale',
        'tagline'     => 'Merchant onboarding, QR/POS rollout, lending sales & field collections across India.',
        'meta_title'  => 'FinTech Field Execution Partner India | Bisani Brothers',
        'meta_desc'   => 'Scale FinTech products with Bisani Brothers — merchant onboarding, distributor networks, digital payment rollout, and collection teams across Tier 2 & 3 India.',
        'icon'        => 'fa-solid fa-mobile-screen-button',
        'services'    => ['Sales & Growth', 'Lending & Collection', 'Staffing Solutions', 'Survey & Market Research'],
        'highlights'  => [
            'Merchant & QR onboarding at scale',
            'Partner-led distributor network activation',
            'Field sales for lending & credit products',
            'Multi-city rollout with daily MIS',
        ],
        'stats'       => [
            ['value' => '50+', 'label' => 'Cities covered'],
            ['value' => '1000+', 'label' => 'Field associates deployed'],
            ['value' => '3x', 'label' => 'Avg. onboarding uplift'],
        ],
        'body'        => 'FinTech companies need speed, compliance, and consistent on-ground quality. Bisani Brothers deploys trained field teams for merchant acquisition, soundbox/QR activation, lending sales, and EMI collections — with structured reporting and city-wise accountability.',
    ],
    'bfsi-nbfc' => [
        'name'        => 'BFSI & NBFC',
        'headline'    => 'BFSI & NBFC On-Ground Operations',
        'tagline'     => 'Collection, sales, verification & workforce deployment for financial services.',
        'meta_title'  => 'BFSI NBFC Field Operations India | Bisani Brothers',
        'meta_desc'   => 'NBFC and BFSI field execution — collection teams, loan sales, KYC verification, and bulk hiring across India by Bisani Brothers.',
        'icon'        => 'fa-solid fa-building-columns',
        'services'    => ['Lending & Collection', 'Staffing Solutions', 'Sales & Growth'],
        'highlights'  => [
            'Collection & recovery field teams',
            'Loan DSA and sales associate deployment',
            'Bulk hiring with onboarding & compliance',
            'Performance tracking & retention support',
        ],
        'stats'       => [
            ['value' => '200+', 'label' => 'Executives deployed per project'],
            ['value' => '85%', 'label' => 'Retention in Q1'],
            ['value' => '24h', 'label' => 'Mobilization capability'],
        ],
        'body'        => 'NBFCs and BFSI brands rely on disciplined field operations for collections, loan origination, and customer verification. We provide deployment-ready teams with training, documentation support, and weekly performance reviews.',
    ],
    'retail' => [
        'name'        => 'Retail & Consumer',
        'headline'    => 'Retail Activation & Field Sales',
        'tagline'     => 'Shop-to-shop sales, retail audits, and brand visibility programs.',
        'meta_title'  => 'Retail Field Sales & BTL India | Bisani Brothers',
        'meta_desc'   => 'Retail execution partner — shop-to-shop sales, store audits, promoter deployment, and BTL activation across India.',
        'icon'        => 'fa-solid fa-store',
        'services'    => ['Sales & Growth', 'BTL & ATL Activation', 'Survey & Market Research'],
        'highlights'  => [
            'Mobile & consumer electronics retail push',
            'In-store promoter deployment',
            'Retail audit & visibility tracking',
            'Launch campaigns & sampling drives',
        ],
        'stats'       => [
            ['value' => '500+', 'label' => 'Retail outlets/month'],
            ['value' => '15+', 'label' => 'States operational'],
            ['value' => 'Daily', 'label' => 'Field reporting'],
        ],
        'body'        => 'Consumer brands need feet-on-street to win shelf space and drive conversions. Bisani Brothers manages retail-focused sales teams, BTL activations, and market visibility programs with measurable daily output.',
    ],
    'ev-mobility' => [
        'name'        => 'EV & Green Mobility',
        'headline'    => 'EV Infrastructure Rollout Support',
        'tagline'     => 'Charging station site surveys, partner onboarding & field verification.',
        'meta_title'  => 'EV Charging Rollout Partner India | Bisani Brothers',
        'meta_desc'   => 'EV infrastructure rollout support — site identification, partner onboarding, field verification, and on-ground deployment across India.',
        'icon'        => 'fa-solid fa-charging-station',
        'services'    => ['EV Infrastructure', 'Survey & Market Research', 'Sales & Growth'],
        'highlights'  => [
            'Site survey & feasibility checks',
            'Partner/dealer onboarding programs',
            'Field verification & documentation',
            'Multi-city rollout coordination',
        ],
        'stats'       => [
            ['value' => '100+', 'label' => 'Sites surveyed'],
            ['value' => 'Pan-India', 'label' => 'Coverage model'],
            ['value' => 'Fast', 'label' => 'Mobilization'],
        ],
        'body'        => 'EV ecosystem players need reliable field teams for site identification, partner onboarding, and rollout execution. We support green mobility expansion with structured on-ground operations and reporting.',
    ],
    'education' => [
        'name'        => 'Education & EdTech',
        'headline'    => 'Education Sector Field Programs',
        'tagline'     => 'Enrollment drives, campus outreach & survey programs.',
        'meta_title'  => 'Education Field Operations India | Bisani Brothers',
        'meta_desc'   => 'Education and EdTech field execution — enrollment campaigns, institutional outreach, and survey programs by Bisani Brothers.',
        'icon'        => 'fa-solid fa-graduation-cap',
        'services'    => ['Survey & Market Research', 'BTL & ATL Activation', 'Staffing Solutions'],
        'highlights'  => [
            'Institutional & campus outreach',
            'Enrollment & lead generation drives',
            'Market surveys & data collection',
            'Temporary promoter & associate hiring',
        ],
        'stats'       => [
            ['value' => 'Tier 2/3', 'label' => 'City focus'],
            ['value' => 'Trained', 'label' => 'Field teams'],
            ['value' => 'Verified', 'label' => 'Data quality'],
        ],
        'body'        => 'Education providers expanding into new regions need trusted field partners for outreach, surveys, and localized activation. We deliver trained teams with clear KPIs and reporting.',
    ],
    'telecom-digital' => [
        'name'        => 'Telecom & Digital Services',
        'headline'    => 'Telecom & Digital Product Rollout',
        'tagline'     => 'SIM, recharge, device & digital service distribution at scale.',
        'meta_title'  => 'Telecom Field Sales Partner India | Bisani Brothers',
        'meta_desc'   => 'Telecom and digital services field execution — distribution, retailer onboarding, and sales teams across India.',
        'icon'        => 'fa-solid fa-tower-cell',
        'services'    => ['Sales & Growth', 'Staffing Solutions', 'BTL & ATL Activation'],
        'highlights'  => [
            'Retailer & distributor onboarding',
            'SIM and recharge product push',
            'Device & accessory sales programs',
            'Territory-wise team management',
        ],
        'stats'       => [
            ['value' => 'Multi-state', 'label' => 'Operations'],
            ['value' => 'High', 'label' => 'Daily activations'],
            ['value' => 'Structured', 'label' => 'MIS & reporting'],
        ],
        'body'        => 'Telecom and digital service providers need consistent retailer penetration and activation velocity. Bisani Brothers manages territory teams, onboarding workflows, and daily performance tracking.',
    ],
];

function industry_get_all(): array
{
    $pages = INDUSTRY_PAGES;
    if (locale_current() === 'hi') {
        $hiFile = dirname(__DIR__) . '/lang/hi/industries.php';
        if (is_file($hiFile)) {
            $hi = require $hiFile;
            foreach ($pages as $slug => &$page) {
                if (isset($hi[$slug])) {
                    $page = array_merge($page, $hi[$slug]);
                }
            }
            unset($page);
        }
    } else {
        $loc = locale_current();
        $locFile = dirname(__DIR__) . '/lang/' . $loc . '/industries.php';
        if ($loc !== 'en' && is_file($locFile)) {
            $tr = require $locFile;
            foreach ($pages as $slug => &$page) {
                if (isset($tr[$slug])) {
                    $page = array_merge($page, $tr[$slug]);
                }
            }
            unset($page);
        }
    }
    return $pages;
}

function industry_get(string $slug): ?array
{
    $all = industry_get_all();
    return $all[$slug] ?? null;
}

function industry_url(string $slug, ?string $locale = null): string
{
    require_once __DIR__ . '/locale.php';
    return locale_url('industries/' . rawurlencode($slug), $locale);
}
