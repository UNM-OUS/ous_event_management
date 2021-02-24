<?php
$package->cache_noStore();
$list = $package->noun();

echo "<h2>Signups using this list</h2>";
echo "<p>The following signup forms are linked to this list:</p>";

echo "<ul>";
foreach ($cms->helper('graph')->parents($list['dso.id'], 'event-signupwindow-userlist') as $window) {
    echo "<li>" . $window->eventGroup()->link() . ": " . $window->link() . "</li>";
}
echo "</ul>";
