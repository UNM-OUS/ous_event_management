<?php
$noun = $package->noun();
$s = $cms->helper('strings');

/**
 * Event body
 */
echo $noun->body();

/**
 * List current signup windows
 */
if ($windows = $noun->currentSignupWindows()) {
    echo "<div class='notification notification-confirmation'>";
    echo "<h2>Sign up</h2>";
    foreach ($windows as $w) {
        echo "<div class=''><strong>" . $w->link() . "</strong> closes " . $s->dateTimeHTML($w['signupwindow.time.end']) . "</div>";
    }
    echo "</div>";
}

/**
 * List primary events
 */
$events = $noun->primaryEvents();
if (count($events) == 0) {
    // there are no primary events
    $cms->helper('notifications')->printWarning('No primary events defined');
} elseif (count($events) == 1) {
    // there is one primary event, just embed its info on the page
    $event = reset($events);
    if ($event->title() != $noun->title()) {
        echo "<h2><a href='" . $event->url() . "'>" . $event->title() . "</a></h2>";
    }
    echo $event->metaBlock();
    echo $event->body();
} else {
    //there are multiple primary events, list them
    echo "<h2>Events</h2>";
    foreach ($events as $event) {
        echo "<div class='digraph-card'>";
        echo "<h2><a href='" . $event->url() . "'>" . $event->title() . "</a></h2>";
        echo $event->metaBlock();
        echo "</div>";
    }
}

/** display secondary events */
if ($noun->secondaryEvents()) {
    $link = $noun->link(null, 'secondary-events');
    $link->addClass('cta-button');
    $link->attr('style', 'display:block;');
    echo "<p>$link</p>";
}

/**
 * List upcoming signup windows
 */
if ($windows = $noun->upcomingSignupWindows()) {
    echo "<div class='notification notification-notice'>";
    echo "<h2>Upcoming signup windows</h2>";
    foreach ($windows as $w) {
        echo "<div class=''><strong>" . $w->link() . "</strong> opens " . $s->dateTimeHTML($w['signupwindow.time.start']) . "</div>";
    }
    echo "</div>";
}

/**
 * List past signup windows
 */
if ($windows = $noun->pastSignupWindows()) {
    echo "<div class='digraph-card'>";
    echo "<h2>Past signup windows</h2>";
    foreach ($windows as $w) {
        echo "<div class=''><strong>" . $w->link() . "</strong> closed " . $s->dateTimeHTML($w['signupwindow.time.end']) . "</div>";
    }
    echo "</div>";
}
