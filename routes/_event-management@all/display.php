<?php
$package->cache_noCache();

$links = [
    'Personalized pages' => [
        [
            '_event-management/page-moderation',
            'View and approve/deny personalized pages that require moderation',
        ],
    ],
    'Reporting' => [
        [
            '_event-management/reportbuilder',
            'Design and produce custom reports',
        ],
        [
            '_event-management/photos',
            'View and download all personalized page photos',
        ],
    ],
    'Email' => [
        [
            '_event-management/mail-templates',
            'Edit the templates used for emails',
        ],
    ],
];

foreach ($links as $section => $sl) {
    $shtml = '';
    foreach ($sl as $l) {
        $shtml .= "<dl>";
        if ($url = $cms->helper('urls')->parse($l[0])) {
            if ($cms->helper('permissions')->checkUrl($url)) {
                $shtml .= "<dt>" . $url->html() . "</dt>";
                $shtml .= "<dd>" . $l[1] . "</dd>";
            }
        }
        $shtml .= "</dl>";
    }
    if ($shtml != '<dl></dl>') {
        echo "<h2>$section</h2>" . $shtml;
    }
}
