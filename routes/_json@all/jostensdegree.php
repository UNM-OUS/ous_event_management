<?php
$package->cache_public();
$package['response.ttl'] = 86400;
$package->makeMediaFile('results.json');
$q = $package['url.args.term'];
$definitive = $package['url.args._definitive'] == 'true';

if ($definitive) {
    // get single result from string
    $results = [$cms->helper('jostens')->locateDegree($q)];
} else {
    // search spreadsheet
    $results = $cms->helper('jostens')->queryDegree($q);
}

// convert format
$results = array_map(
    function ($e) {
        return [
            'label' => $e['degree'],
            'value' => $e['degree'],
        ];
    },
    $results
);
if ($definitive) {
    $results = $results[0];
}

// return json encoded
echo json_encode($results);
