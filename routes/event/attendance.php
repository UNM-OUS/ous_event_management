<?php
$package->cache_noStore();

/** @var \Digraph\Modules\ous_event_management\Event */
$event = $package->noun();

$signupIDs = $event->allSignupIDs();
$signupInClause = "\${dso.id} in ('" . implode("','", $signupIDs) . "')";

$search = $cms->factory()->search();
$search->where('${dso');
