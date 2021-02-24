<?php
$package->cache_noStore();
$signup = $package->noun();
$package['response.template'] = 'iframe.twig';

if (!($page = $signup->personalizedPage())) {
    $package->error(404);
    return;
}

$package['fields.page_name'] = $signup->contactInfo()->name();
echo $page->pageBody();
