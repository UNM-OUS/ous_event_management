<?php
$package->cache_noStore();

/** @var \Digraph\Users\UserHelper */
$users = $cms->helper('users');
$user = $users->user();
/** @var \Digraph\Modules\event_attendance\TicketGroup */
$group = $package->noun();

$signups = $group->signupsFor($user);
if (!$signups) {
    $cms->helper('notifications')->printWarning('No tickets found for <code>' . $user->identifier() . '</code>');
    if (!$group->isEditable()) {
        return;
    }
} elseif (count($signups) == 1) {
    $url = $group->url('ticket');
    $url['args.s'] = $signups[0]['dso.id'];
    if (!$group->isEditable()) {
        $package->redirect($url);
    }
}

echo "<p>The following tickets are associated with your account, either by being for you or created by you.</p>";
echo "<ul>";
foreach ($signups as $signup) {
    $url = $group->url('ticket');
    $url['args.s'] = $signup['dso.id'];
    echo "<li><a href='$url'>" . $signup->name() . "</a></li>";
}
echo "</ul>";

if ($group->isEditable()) {
    echo "<div class='digraph-card'>";
    echo "<h2>Admin info</h2>";
    echo "<div>Total potential tickets available (includes incomplete signups): " . count($group->signupIDs());
    echo "</div>";
}
