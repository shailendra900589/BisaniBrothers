<?php
header('Content-Type: application/opensearchdescription+xml; charset=utf-8');

require_once __DIR__ . '/includes/seo-config.php';
require_once __DIR__ . '/includes/seo.php';

$base = seo_site_url_rtrim();
$site = SEO_SITE_NAME;

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
    <ShortName><?php echo htmlspecialchars($site); ?></ShortName>
    <Description>Search Bisani Brothers blog articles, services, and career opportunities</Description>
    <Tags>Bisani Brothers business solutions staffing sales India</Tags>
    <Contact>contact@bisanibrother.com</Contact>
    <Url type="text/html" template="<?php echo htmlspecialchars($base); ?>/search?q={searchTerms}"/>
    <Url type="application/rss+xml" template="<?php echo htmlspecialchars($base); ?>/rss.xml"/>
    <Image height="16" width="16" type="image/png"><?php echo htmlspecialchars($base); ?>/assets/favicon/favicon-32x32.png</Image>
    <InputEncoding>UTF-8</InputEncoding>
    <OutputEncoding>UTF-8</OutputEncoding>
    <Language>en-in</Language>
    <SyndicationRight>open</SyndicationRight>
    <AdultContent>false</AdultContent>
</OpenSearchDescription>
