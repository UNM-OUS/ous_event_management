<?php
$package['response.ttl'] = 300;

$search = $cms->factory()->search();
$search->where('${dso.type} = "event-group" AND CAST(${eventgroup.launchtime} AS int) <> 0 AND CAST(${eventgroup.launchtime} AS int) < :time');
$search->order('${eventgroup.launchtime} desc');
$search->limit(1);
$search->offset(0);
$result = $search->execute(['time' => time()]);
if ($result) {
    $result = array_pop($result);
} else {
    $cms->helper('notifications')->printError('No current event group found. Please check back later.');
    return;
}

$package->redirect($result->url(), 307);
