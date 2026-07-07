<?php
/**
 * Demo case studies — shown when the database has no published entries yet.
 * Replace by running: php scripts/seed-case-studies.php
 *
 * @return array<int, array<string, mixed>>
 */
function case_study_demo_entries(): array
{
    return [
        [
            'id'           => 1,
            'title'        => 'Scaling Merchant Onboarding Across Tier 2 Cities',
            'slug'         => 'scaling-merchant-onboarding-tier-2-cities',
            'client_name'  => 'Leading FinTech Partner',
            'industry'     => 'FinTech',
            'service_line' => 'Sales & Growth',
            'challenge'    => '<p>A digital payments company needed to activate merchants across eight Tier 2 cities within one quarter. Internal teams could not maintain visit quality, follow-up discipline, or daily reporting while entering new territories simultaneously.</p>',
            'approach'     => '<p>Bisani Brothers deployed structured feet-on-street teams with territory mapping, distributor coordination, and daily onboarding targets. Supervisors tracked visit logs, document completion, and activation status through centralized MIS reporting.</p>',
            'results'      => '<p>3× increase in daily merchant activations, 40% reduction in onboarding turnaround time, and consistent field quality across all eight cities within 90 days of deployment.</p>',
            'quote'        => 'Their on-ground execution model helped us scale where internal teams struggled to maintain speed and quality.',
            'content'      => '<p>This engagement shows how partner-led sales and service support accelerates FinTech rollout in markets where speed and accountability matter equally.</p>',
            'image_path'   => '',
            'meta_title'   => 'FinTech Merchant Onboarding Case Study | Bisani Brothers',
            'meta_desc'    => 'How Bisani Brothers scaled merchant onboarding across Tier 2 India with structured field teams and daily reporting.',
            'keywords'     => 'FinTech merchant onboarding, field sales India, Tier 2 expansion',
            'is_published' => 1,
            'locale'       => 'en',
            'created_at'   => '2025-11-10 10:00:00',
        ],
        [
            'id'           => 2,
            'title'        => 'Bulk Field Hiring for NBFC Collection Operations',
            'slug'         => 'bulk-field-hiring-nbfc-collection',
            'client_name'  => 'NBFC Operations Partner',
            'industry'     => 'BFSI / NBFC',
            'service_line' => 'Staffing Solutions',
            'challenge'    => '<p>An NBFC required 200+ collection executives across Uttar Pradesh within six weeks. Hiring delays, attrition, and inconsistent training were affecting recovery performance and compliance standards.</p>',
            'approach'     => '<p>We managed end-to-end recruitment, background verification, onboarding, and field deployment with weekly performance reviews. Third-party payroll coordination kept associates deployment-ready without adding admin load to the client HR team.</p>',
            'results'      => '<p>200+ executives deployed in 6 weeks, 85% retention in the first quarter, and measurable improvement in collection efficiency and borrower engagement quality.</p>',
            'quote'        => 'BBPL delivered trained field staff faster than any agency we worked with before.',
            'content'      => '<p>Structured staffing and onboarding enabled consistent collection performance at scale while maintaining ethical customer engagement practices.</p>',
            'image_path'   => '',
            'meta_title'   => 'NBFC Staffing Case Study India | Bisani Brothers',
            'meta_desc'    => 'Bulk hiring and field workforce deployment for NBFC collection operations by Bisani Brothers.',
            'keywords'     => 'NBFC staffing, collection field force, third party payroll India',
            'is_published' => 1,
            'locale'       => 'en',
            'created_at'   => '2025-10-22 10:00:00',
        ],
        [
            'id'           => 3,
            'title'        => 'Pan-India BTL Activation for Consumer Brand Launch',
            'slug'         => 'pan-india-btl-brand-launch-activation',
            'client_name'  => 'National Consumer Brand',
            'industry'     => 'Retail & FMCG',
            'service_line' => 'BTL & ATL Activation',
            'challenge'    => '<p>A consumer brand launching a new product line needed high-visibility on-ground activation across 12 cities with sampling, engagement tracking, and last-mile retailer follow-up — all within a tight campaign window.</p>',
            'approach'     => '<p>Bisani Brothers planned brand promotion activity with trained promoter teams, standardized campaign kits, geo-mapped activity logs, and daily supervisor audits. Retail and feet-on-street touchpoints were coordinated for maximum local reach.</p>',
            'results'      => '<p>1.2M+ consumer interactions logged, 35% uplift in trial-to-purchase conversion in activation zones, and full campaign MIS delivered within 48 hours of daily close.</p>',
            'quote'        => 'The campaign execution was structured, measurable, and delivered the on-ground visibility we needed for launch week.',
            'content'      => '<p>BTL ATL field force services combined with disciplined reporting helped the brand translate promotional spend into measurable local market impact.</p>',
            'image_path'   => '',
            'meta_title'   => 'BTL Brand Activation Case Study | Bisani Brothers',
            'meta_desc'    => 'Pan-India BTL field force and brand promotion activity for a consumer product launch by Bisani Brothers.',
            'keywords'     => 'BTL activation India, brand promotion activity, field marketing',
            'is_published' => 1,
            'locale'       => 'en',
            'created_at'   => '2025-09-15 10:00:00',
        ],
        [
            'id'           => 4,
            'title'        => 'EV Charging Network Rollout Across 5 States',
            'slug'         => 'ev-charging-network-rollout-five-states',
            'client_name'  => 'EV Infrastructure Company',
            'industry'     => 'EV & Green Mobility',
            'service_line' => 'EV Infrastructure Support',
            'challenge'    => '<p>An EV infrastructure provider needed faster site identification, partner onboarding, and field verification across five states while internal teams were focused on technology and installer coordination.</p>',
            'approach'     => '<p>Our EV support services teams conducted location feasibility checks, property owner outreach, document verification, and site readiness validation. Structured verification reports reduced installation delays and improved rollout planning accuracy.</p>',
            'results'      => '<p>180+ sites verified and onboarded in 4 months, 30% reduction in site rework, and improved coordination between field teams and installation partners.</p>',
            'quote'        => 'Their verification and onboarding discipline helped us expand the network faster with fewer last-minute site issues.',
            'content'      => '<p>Field verification services and on-ground partner alignment proved critical for scaling EV charging infrastructure beyond metro markets.</p>',
            'image_path'   => '',
            'meta_title'   => 'EV Infrastructure Rollout Case Study | Bisani Brothers',
            'meta_desc'    => 'EV support services and field verification for multi-state charging network expansion by Bisani Brothers.',
            'keywords'     => 'EV support services, verification services, charging station rollout',
            'is_published' => 1,
            'locale'       => 'en',
            'created_at'   => '2025-08-05 10:00:00',
        ],
        [
            'id'           => 5,
            'title'        => 'Lending Sales & Tele-Collection for Digital Lender',
            'slug'         => 'lending-sales-tele-collection-digital-lender',
            'client_name'  => 'Digital Lending Platform',
            'industry'     => 'FinTech / Lending',
            'service_line' => 'Lending & Collection',
            'challenge'    => '<p>A digital lender scaling personal loan products needed compliant field sales for sourcing and a tele-collection layer for early-stage delinquency — without compromising borrower experience or regulatory standards.</p>',
            'approach'     => '<p>Bisani Brothers deployed financial services provider teams for loan sourcing in priority territories and a dedicated call centre pod for structured follow-ups. Scripts, escalation paths, and daily recovery MIS were aligned with client compliance guidelines.</p>',
            'results'      => '<p>45% increase in qualified loan applications from field channels, improved early-bucket recovery rates, and zero major compliance escalations during the first two quarters.</p>',
            'quote'        => 'They balanced recovery targets with respectful borrower communication — exactly what we needed at scale.',
            'content'      => '<p>Combining field sourcing with tele-collection support gave the lender consistent growth and recovery performance across multiple cities.</p>',
            'image_path'   => '',
            'meta_title'   => 'Lending & Collection Case Study | Bisani Brothers',
            'meta_desc'    => 'Financial services field sales and tele-collection support for a digital lending platform in India.',
            'keywords'     => 'financial services provider, lending collection India, tele-collection',
            'is_published' => 1,
            'locale'       => 'en',
            'created_at'   => '2025-07-18 10:00:00',
        ],
        [
            'id'           => 6,
            'title'        => 'On-Ground Market Research for Retail Expansion',
            'slug'         => 'on-ground-market-research-retail-expansion',
            'client_name'  => 'Retail Expansion Team',
            'industry'     => 'Retail',
            'service_line' => 'Survey & Market Research',
            'challenge'    => '<p>A retail chain planning Tier 2 expansion needed reliable on-ground data on footfall, competitor presence, and merchant sentiment — desk research alone could not validate site selection decisions.</p>',
            'approach'     => '<p>We deployed survey teams across 30 locations with structured questionnaires, photo validation where required, and daily data uploads. Field supervisors audited sample quality and corrected gaps in real time.</p>',
            'results'      => '<p>30-location assessment completed in 3 weeks, actionable site shortlist delivered, and 22% improvement in new store performance vs prior expansion cycle benchmarks.</p>',
            'quote'        => 'The field data gave our leadership team confidence to invest in the right cities first.',
            'content'      => '<p>On-ground survey execution turned expansion planning from assumption-driven to evidence-backed decision making.</p>',
            'image_path'   => '',
            'meta_title'   => 'Retail Market Research Case Study | Bisani Brothers',
            'meta_desc'    => 'On-ground survey and market research for retail expansion planning across Tier 2 India.',
            'keywords'     => 'market research India, survey execution, retail expansion',
            'is_published' => 1,
            'locale'       => 'en',
            'created_at'   => '2025-06-01 10:00:00',
        ],
    ];
}

function case_study_demo_by_slug(string $slug): ?array
{
    foreach (case_study_demo_entries() as $entry) {
        if ($entry['slug'] === $slug) {
            return $entry;
        }
    }

    return null;
}
