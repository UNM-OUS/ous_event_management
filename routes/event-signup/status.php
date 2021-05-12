<?php
$package->cache_noStore();
$package->makeMediaFile('status.json');
$signup = $package->noun();

$status = [
    'type' => 'none',
    'message' => 'Unknown signup status',
];

if (!$signup->allowViewing()) {
    $package->error(403);
    return;
}

if ($signup->complete()) {
    $status = [
        'type' => 'confirmation',
        'message' => 'This signup is complete'
    ];
} else {
    if ($signup->allowUpdate()) {
        $status = [
            'type' => 'warning',
            'message' => 'This signup is currently incomplete. Please complete all the sections below.'
        ];
    } else {
        $status = [
            'type' => 'error',
            'message' => 'This signup is incomplete'
        ];
    }
}

$status['name'] = $signup->name();
echo json_encode($status);
