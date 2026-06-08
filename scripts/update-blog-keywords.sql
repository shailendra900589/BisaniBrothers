-- Bisani Brothers: blog keywords & missing meta descriptions (content-aligned SEO)
UPDATE blogs SET
  keywords = 'FinTech scaling mistakes, FinTech growth India, digital onboarding FinTech, sales process standardization, FinTech execution, compliance onboarding India, Bisani Brothers',
  meta_desc = COALESCE(NULLIF(TRIM(meta_desc), ''), 'Learn the biggest mistakes FinTech companies make while scaling, from onboarding gaps to poor execution, and how to avoid them with the right strategy.')
WHERE id = 2;

UPDATE blogs SET
  keywords = 'Tier 2 Tier 3 expansion, local networks India, national business growth, on-ground execution, field teams India, rural market expansion, FinTech growth India, Bisani Brothers',
  meta_desc = COALESCE(NULLIF(TRIM(meta_desc), ''), 'Discover how local networks and on-ground execution in Tier 2 and Tier 3 cities drive national business growth beyond metro markets in India.')
WHERE id = 4;

UPDATE blogs SET
  keywords = 'execution at scale, on-ground partner India, business growth partner, field execution teams, scalable operations, sales rollout India, Bisani Brothers',
  meta_desc = COALESCE(NULLIF(TRIM(meta_desc), ''), 'Learn how companies grow faster with the right on-ground execution partner through structured field teams and scalable rollout across India.')
WHERE id = 5;

UPDATE blogs SET
  keywords = 'India workforce development, employment opportunities India, field workforce, manpower growth India, staffing solutions, Bisani Brothers careers, on-ground jobs',
  meta_desc = COALESCE(NULLIF(TRIM(meta_desc), ''), 'Explore how Bisani Brothers is building India workforce of the future through structured field teams, employment opportunities, and nationwide growth.')
WHERE id = 6;

UPDATE blogs SET
  keywords = 'distributor network India, merchant onboarding scale, QR onboarding, FinTech merchant acquisition, payment agent network, field sales FinTech, market expansion',
  meta_desc = COALESCE(NULLIF(TRIM(meta_desc), ''), 'Why distributor networks are the fastest way to scale merchant onboarding and drive FinTech market expansion across India.')
WHERE id = 7;

UPDATE blogs SET
  keywords = 'multi-city project execution, rollout planning India, field project management, EV infrastructure rollout, structured deployment, on-ground coordination, Bisani Brothers',
  meta_desc = COALESCE(NULLIF(TRIM(meta_desc), ''), 'Why structured rollout planning is essential for multi-city project execution and how disciplined field deployment improves outcomes.')
WHERE id = 8;

UPDATE blogs SET
  keywords = 'partner-led onboarding, merchant onboarding India, market expansion strategy, field partner network, FinTech growth, on-ground sales execution, Bisani Brothers',
  meta_desc = COALESCE(NULLIF(TRIM(meta_desc), ''), 'Partner-led merchant onboarding drives faster market expansion. Learn how execution partners help businesses scale onboarding across regions.')
WHERE id = 9;

UPDATE blogs SET
  keywords = 'top finance companies India, business finance Lucknow, NBFC partners India, business loan India, finance company Lucknow, Bisani Brothers finance',
  meta_desc = COALESCE(NULLIF(TRIM(meta_desc), ''), 'Bisani Brothers helps businesses connect with top finance companies in India for funding, expansion, and long-term business growth.')
WHERE id = 10;

UPDATE blogs SET
  keywords = 'best finance company near me, quick loan approval Lucknow, personal loan Lucknow, business loan approval, finance company near me, loan services India, Bisani Brothers',
  meta_desc = COALESCE(NULLIF(TRIM(meta_desc), ''), 'Find the best finance company near me in Lucknow with Bisani Brothers. Quick loan approval, trusted financial services, and expert support across India.')
WHERE id = 11;

UPDATE blogs SET
  keywords = 'finance company near me, fast loan approval Lucknow, personal loan interest rates, business loan rates, home loan options Lucknow, Bisani Brothers loans',
  meta_desc = COALESCE(NULLIF(TRIM(meta_desc), ''), 'Get quick loan approvals in Lucknow with Bisani Brothers. Compare personal loan, business loan, and home loan options and apply easily.')
WHERE id = 12;
