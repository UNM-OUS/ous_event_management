<?php
$signup = $package->noun();
$package['response.headers.x-robots-tag'] = 'noindex';
$package['response.ttl'] = 86400;

if (!($page = $signup->personalizedPage())) {
    $package->error(404);
    return;
}

if ($page->pageModeration() === null) {
    $cms->helper('notifications')->printWarning('This page\'s content is awaiting moderation');
    return;
}

if ($page->pageModeration() === false) {
    $cms->helper('notifications')->printWarning('This page\'s content has been blocked by a moderator');
    return;
}

$package['fields.page_name'] = $signup->contactInfo()->name();
?>
<script type="text/javascript" src="https://platform-api.sharethis.com/js/sharethis.js#property=5f91b1cf0c30ea00126bd310&product=inline-share-buttons" async="async"></script>
<div class="sharethis-inline-share-buttons"></div>
<?php
echo $page->pageBody();
