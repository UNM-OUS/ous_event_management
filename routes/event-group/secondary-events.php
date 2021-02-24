<?php
$package['fields.page_title'] = $package['fields.page_name'] = $package['url.text'];

$noun = $package->noun();

$categories = ['_uncategorized' => []];
foreach ($noun->secondaryEvents() as $e) {
    if ($cat = $e->category()) {
        $categories[$cat][] = $e;
    } else {
        $categories['_uncategorized'][] = $e;
    }
}
ksort($categories);
foreach ($categories as $cat => $events) {
    if (!$events) {
        continue;
    }
    if ($cat != '_uncategorized') {
        echo "<h2>$cat</h2>";
    }
    echo "<table>";
    foreach ($events as $event) {
        if ($event['cancelled']) {
            echo "<tr class='highlighted-warning'>";
            echo "<td><strong><a href='" . $event->linkUrl() . "'>" . $event->title() . "</a></strong></td>";
            echo "<td><div class='incidental'>CANCELLED</div></td>";
            echo "</tr>";
        } else {
            echo "<tr>";
            echo "<td><strong><a href='" . $event->linkUrl() . "'>" . $event->title() . "</a></strong></td>";
            echo "<td><div class='incidental'>";
            echo $event->metaCell();
            echo "</div></td>";
            echo "</tr>";
        }
    }
    echo "</table>";
}
