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
    $s = $cms->helper('strings');
    echo "<div class='notification notification-confirmation'>";
    foreach ($windows as $w) {
        echo "<p><strong>" . $w->link() . "</strong><br>closes " . $s->dateTimeHTML($w['signupwindow.time.end']) . "</p>";
    }
    echo "</div>";
}
