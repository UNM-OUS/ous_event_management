<?php
/**
 * List ended signup windows
 */
$windows = $package->noun()->signupWindows();
$windows = array_filter(
    $windows,
    function ($e) {
        return !$e->isOpen() && !$e['signupwindow.unlisted'];
    }
);
if ($windows) {
    echo "<div class='digraph-card incidental'>";
    echo "<h2>Closed or pending signup windows</h2>";
    echo "<ul>";
    foreach ($windows as $w) {
        $link = $w->url();
        $link['args.from'] = $package['noun.dso.id'];
        echo "<li>" . $link->html() . "</li>";
    }
    echo "</ul>";
    echo '</div>';
}
