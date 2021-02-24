<?php
$package->cache_noStore();
$package['response.template'] = 'iframe.twig';
$package['fields.page_name'] = '';

// mode switching links
$url = $package->url();
echo "<div class='navbar'>";
$url['args.mode'] = false;
echo "<a class='menuitem' href='$url' class='" . ($package['url.args.mode'] ? '' : 'selected active-page') . "'>Moderation Queue</a>";
$url['args.mode'] = 'escalated';
echo "<a class='menuitem' href='$url' class='" . ($package['url.args.mode'] == 'escalated' ? 'selected active-page' : '') . "'>Escalated Queue</a>";
$url['args.mode'] = 'spot';
echo "<a class='menuitem' href='$url' class='" . ($package['url.args.mode'] == 'spot' ? 'selected active-page' : '') . "'>Spot Check (approved)</a>";
$url['args.mode'] = 'spot-denied';
echo "<a class='menuitem' href='$url' class='" . ($package['url.args.mode'] == 'spot' ? 'selected active-page' : '') . "'>Spot Check (denied)</a>";
echo "</div>";

// check for action/token in the args
if ($id = $package['url.args.dso_id']) {
    if ($cms->helper('session')->checkToken('personalpage.moderate.' . $id, $package['url.args.token'])) {
        if ($target = $cms->read($id)) {
            switch ($package['url.args.action']) {
                case 'approve':
                    $cms->helper('notifications')->printConfirmation('Page approved');
                    $target->personalizedPage()->pageModerate(true);
                    break;
                case 'deny':
                    $cms->helper('notifications')->printConfirmation('Page denied');
                    $target->personalizedPage()->pageModerate(false);
                    break;
                case 'escalate':
                    $cms->helper('notifications')->printConfirmation('Page escalated');
                    $target->personalizedPage()->pageModerationEscalate();
                    break;
            }
        }
    }
}

// locate a page to moderate
$search = $cms->factory()->search();
$where = [
    '${dso.type} = "event-signup"',
    '${personalpage.activate} is not null',
    '${complete.state} = "complete"'
];
switch ($package['url.args.mode']) {
    case 'escalated':
        $cms->helper('notifications')->printNotice("Mode: escalated moderation, viewing escalated, unmoderated pages");
        $where[] = '${moderation.state} = "escalated"';
    break;
    case 'spot':
        $cms->helper('notifications')->printNotice("Mode: spot check, viewing random approved pages");
        $where[] = '${moderation.state} = "approved"';
    break;
    case 'spot-denied':
        $cms->helper('notifications')->printNotice("Mode: spot check, viewing random denied pages");
        $where[] = '${moderation.state} = "denied"';
    break;
    default:
        $cms->helper('notifications')->printNotice("Mode: moderation, viewing unmoderated pages");
        $where[] = '${moderation.state} = "pending"';
}
// assemble search object
$search->where(implode(' AND ', $where));
// pick random result from offset zero to count-1
$count = $search->count();
if ($count > 1) {
    $offset = random_int(0, $count - 1);
    $search->limit(1);
    $search->offset($offset);
}
// indicate queue status
if ($count && $package['url.args.mode'] != 'spot') {
    echo "<div class='notification notification-notice'>Queue contains <strong>".($count-1)."</strong> more pages</div>";
}
// load first result from query
if ($count && $signup = $search->execute()) {
    $signup = $signup[0];
    $person = $signup->firstUserListUser();
    $id = $signup['dso.id'];
    $token = $cms->helper('session')->getToken('personalpage.moderate.' . $id);
    $contact = $signup->contactInfo();
    // moderation buttons
    $url = $package->url();
    $url['args.dso_id'] = $signup['dso.id'];
    $url['args.token'] = $token;
    $url['args.action'] = 'approve';
    echo "<div style='text-align:center;'>";
    echo "<a class='cta-button green' style='width:33%;text-align:center;' href='$url'>Approve</a>";
    $url['args.action'] = 'deny';
    echo "<a class='cta-button red' style='width:33%;text-align:center;' href='$url'>Deny</a>";
    if ($package['url.args.mode'] != 'escalated') {
        $url['args.action'] = 'escalate';
        echo "<a class='cta-button blue' style='width:33%;text-align:center;' href='$url'>Escalate</a>";
    }
    echo "</div>";
    // notification if name is changed
    if ($contact->firstName() != $person['first name']) {
        $cms->helper('notifications')->printNotice("First name changed from " . $person['first name'] . ' to ' . $contact->firstName());
    }
    if ($contact->lastName() != $person['last name']) {
        $cms->helper('notifications')->printNotice("Last name changed from " . $person['last name'] . ' to ' . $contact->lastName());
    }
    // embedded iframe with page
    echo "<div class='digraph-card'>";
    echo "<iframe class='embedded-iframe' src='" . $signup->url('personalpage-preview') . "'></iframe>";
    echo "</div>";
} else {
    $cms->helper('notifications')->printConfirmation('Hooray! There are no personalized pages in this moderation queue.');
}
