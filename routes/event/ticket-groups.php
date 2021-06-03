<?php
$package->cache_noStore();

/** @var \Digraph\Graph\GraphHelper */
$graph = $cms->helper('graph');
$groups = $graph->children($package['noun.dso.id'], 'event-ticket-group');

if (!$groups) {
    $cms->helper('notifications')->printWarning('No ticket groups');
}

echo "<ul>";
foreach ($groups as $group) {
    echo "<li>" . $group->link() . "</li>";
}
echo "</ul>";
