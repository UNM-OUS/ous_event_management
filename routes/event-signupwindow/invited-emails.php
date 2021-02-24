<?php
$window = $package->noun();

$hiddenNetIDs = [];
if ($package['url.args.all'] == 'true') {
    $cms->helper('notifications')->printNotice('Includes people who have a completed signup.<br><a href="?all=false">Hide them instead</a>');
} else {
    $cms->helper('notifications')->printNotice('Hiding people who have a completed signup.<br><a href="?all=true">Show them instead</a>');
    foreach ($window->allSignups() as $signup) {
        if ($signup->complete() && strpos($signup['signup.for'], '@') === false) {
            $hiddenNetIDs[] = $signup['signup.for'];
        }
    }
    $hiddenNetIDs = array_filter($hiddenNetIDs);
    $hiddenNetIDs = array_unique($hiddenNetIDs);
}

$emails = [];
foreach ($cms->helper('graph')->children($window['dso.id'], 'event-signupwindow-userlist') as $list) {
    $count = 0;
    foreach ($list->filter(function () {return true;}) as $row) {
        if (@$row['netid'] && in_array($row['netid'], $hiddenNetIDs)) {
            continue;
        }
        if (@$row['email']) {
            $count++;
            $emails[] = $row['email'];
        }
    }
    $cms->helper('notifications')->printNotice('Included ' . $count . ' invitees from ' . $list->link());
}
$emails = array_filter($emails);
$emails = array_unique($emails);

echo "<p><textarea style='height:20em;width:100%;'>" . implode(PHP_EOL, $emails) . "</textarea></p>";
