<?php
$package->cache_noStore();
$window = $package->noun();

echo "<h2>Current lists</h2>";
echo "<p>The following lists are currently in use for this signup form</p>";

echo "<ul>";
foreach ($cms->helper('graph')->children($window['dso.id'], 'event-signupwindow-userlist') as $list) {
    echo "<li>" . $list->link() . ": <a href='" . $list->url('edit') . "'>edit list</a></li>";
}
echo "</ul>";
?>

<h2>More tools</h2>
<ul>
    <li><a href="<?php echo $window->url('invited-emails'); ?>">List invitee emails</a></li>
</ul>
