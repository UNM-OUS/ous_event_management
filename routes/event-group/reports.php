<?php
$package->cache_noStore();
$group = $package->noun();
$s = $cms->helper('strings');

echo "<h2>All signup windows</h2>";
echo "<table>";
echo "<tr><th></th><th>Open</th><th>Close</th></tr>";
foreach ($group->signupWindows() as $window) {
    echo "<tr>";
    echo "<td>" . $window->link($window->name(), 'reports') . "</td>";
    echo "<td>" . $s->datetimeHTML($window['signupwindow.time.start']) . "</td>";
    echo "<td>" . $s->datetimeHTML($window['signupwindow.time.end']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Primary events</h2>";
echo "<table>";
echo "<tr><th></th><th>&nbsp;</th></tr>";
foreach ($group->primaryEvents() as $event) {
    echo "<tr>";
    echo "<td>" . $event->link($event->name(), 'reports') . "</td>";
    echo "<td>" . $event->metaCell() . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Secondary events</h2>";
echo "<table>";
echo "<tr><th></th><th>&nbsp;</th></tr>";
foreach ($group->secondaryEvents() as $event) {
    echo "<tr>";
    echo "<td>" . $event->link($event->name(), 'reports') . "</td>";
    echo "<td>" . $event->metaCell() . "</td>";
    echo "</tr>";
}
echo "</table>";
