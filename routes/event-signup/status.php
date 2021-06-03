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
    $message = '<strong>This signup is complete</strong>';
    // locate tickets
    $tickets = [];
    /** @var \Digraph\Graph\GraphHelper */
    $graph = $cms->helper('graph');
    foreach ($signup->allEvents() as $event) {
        foreach ($graph->children($event['dso.id'], 'event-ticket-group') as $ticketGroup) {
            /** @var \Digraph\Modules\event_attendance\TicketGroup */
            // $ticketGroup = $ticketGroup;
            if (in_array($signup['dso.id'], $ticketGroup->signupIDs())) {
                $tickets[] = $ticketGroup->link($ticketGroup->name(), 'ticket', ['s' => $signup['dso.id']]);
            }
        }
    }
    if ($tickets) {
        $message .= '<br><br>Print-at-home tickets/passes:<br>'
            . implode('<br>', $tickets);
    }
    $status = [
        'type' => 'confirmation',
        'message' => $message
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
