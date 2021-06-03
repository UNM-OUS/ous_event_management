<?php

use Digraph\Modules\event_attendance\ManualTicket;

$group = $package->noun();
$signupIDs = $group->signupIDs();
$bulk = [];
$individual = [];
$unidentified = [];

foreach ($signupIDs as $sid) {
    if ($signup = $cms->read($sid, false)) {
        if ($signup['complete.state'] == 'complete' || $signup instanceof ManualTicket) {
            if ($signup['signup.for'] && strpos('@', $signup['signup.for'])  === false) {
                // signup is for a NetID, it can be put in bulk mailing
                $bulk[] = $signup['signup.for'] . '@unm.edu';
                $bulk[] = $signup['contact.email'] ?? false;
            } elseif ($signup['signup.for'] || $signup['contact.email']) {
                // signup has at least one email address, but no NetID
                $individual[$signup['dso.id']] = $signup['contact.email'] ?? $signup['signup.for'] ?? false;
            } else {
                // signup has no email or NetID
                $unidentified[] = $signup;
            }
        }
    }
}
$bulk = array_unique(array_filter($bulk));
$individual = array_unique(array_filter($individual));

if ($bulk) {
    echo "<h2>Bulk emails (" . count($bulk) . ")</h2>";
    echo "<p>These email addresses are associated with a NetID and can all be sent the same link, which will bounce them to their individual tickets.</p>";
    echo "<p>Count may be higher than the expected number of completed signups, because if a signup specified a non-main-campus email address, both are included here.</p>";
    echo "<p>The link to provide them with is: <code>" . $group->url() . "</code></p>";
    echo "<p><textarea readonly style='height:5em;display:block;width:100%;'>" . implode(
        PHP_EOL,
        $bulk
    ) . "</textarea></p>";
}

if ($individual) {
    echo "<h2>Individual emails (" . count($individual) . ")</h2>";
    echo "<p>These tickets are associated with an email address but not a NetID. They must be sent individual emails, each with their own URL.</p>";
    echo "<p>Note that without a NetID associated, tickets are accessible to anyone with the URL.</p>";
    echo "<table>";
    echo "<tr><th>Name</th><th>Email</th><th>Ticket URL</th></tr>";
    foreach ($individual as $id => $email) {
        $signup = $cms->read($id, false);
        echo "<tr>";
        echo "<td>" . $signup->name() . "</td>";
        echo "<td>$email</td>";
        echo "<td>" . $group->url('ticket', ['s' => $id]) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

if ($unidentified) {
    echo "<h2>Unidentified tickets (" . count($unidentified) . ")</h2>";
    echo "<p>These tickets are not associated with a NetID or email address, and are accessible to anyone with the link.</p>";
    echo "<table>";
    echo "<tr><th>Name</th><th>Ticket URL</th></tr>";
    foreach ($unidentified as $signup) {
        echo "<tr>";
        echo "<td>" . $signup->name() . "</td>";
        echo "<td>" . $group->url('ticket', ['s' => $signup['dso.id']]) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
