<?php
$package->cache_private();
$event = $package->noun();

/**
 * Override URL redirect
 */
if ($event['override_url']) {
    if ($event->isEditable()) {
        $cms->helper('notifications')->notice(
            'For other users this URL will redirect to <a href="' . $event['override_url'] . '">' . $event['override_url'] . '</a>. You have edit permissions so you are not being redirected.'
        );
    } else {
        $package->redirect($event['override_url']);
    }
}

/**
 * List current signup windows
 */
$windows = $event->signupWindows();
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
        echo "<p><strong>" . $w->link() . "</strong><br><span class='incidental'>closes " . $s->dateTimeHTML($w['signupwindow.time.end']) . "</span></p>";
    }
    echo "</div>";
}
