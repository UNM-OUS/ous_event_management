<?php
/**
 * List current signup windows
 */
$windows = $package->noun()->signupWindows();
$windows = array_filter(
    $windows,
    function ($e) {
        return $e->isOpen() && !$e['signupwindow.unlisted'];
    }
);
if ($windows) {
    echo "<div class='notification notification-confirmation'>";
    echo "<h2>Sign up</h2>";
    echo "<ul>";
    foreach ($windows as $w) {
        $link = $w->url();
        $link['args.from'] = $package['noun.dso.id'];
        echo "<li>" . $link->html() . "</li>";
    }
    echo "</ul>";
    echo "</div>";
}
