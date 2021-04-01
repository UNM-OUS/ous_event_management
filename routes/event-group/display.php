<style>
article.type_event-group > h1:first-child {
    display: none;
}
</style>

<?php
$noun = $package->noun();
$s = $cms->helper('strings');

/**
 * Event body
 */
echo $noun->body();

/**
 * List primary events
 */
$events = $noun->primaryEvents();
if (count($events) == 0) {
    // there are no primary events
    // $cms->helper('notifications')->printWarning('No primary events defined');
} elseif (count($events) == 1) {
    // there is one primary event, just embed its info on the page
    $event = reset($events);
    if ($event->title() != $noun->title()) {
        echo "<h2><a href='" . $event->url() . "'>" . $event->title() . "</a></h2>";
    }
    echo '<div class="digraph-card incidental">' . $event->metaCell() . '</div>';
    echo $event->body();
    echo "<hr>";
} else {
    //there are multiple primary events, list them
    foreach ($events as $event) {
        echo "<div class='digraph-card event-card' id='event-card-" . $event['dso.id'] . "'>";
        echo "<h2><a href='" . $event->url() . "'>" . $event->title() . "</a></h2>";
        echo '<div class="incidental">' . $event->metaCell() . '</div>';
        echo "</div>";
    }
}

/**
 * List current signup windows
 */
if ($windows = $noun->currentSignupWindows()) {
    echo "<div class='notification notification-confirmation'>";
    foreach ($windows as $w) {
        echo "<p><strong>" . $w->link() . "</strong><br><span class='incidental'>closes " . $s->dateTimeHTML($w['signupwindow.time.end']) . "</span></p>";
    }
    echo "</div>";
}

/** display secondary events */
if ($noun->secondaryEvents()) {
    $link = $noun->link(null, 'secondary-events');
    echo "<div class='digraph-card event-card' id='departmental-events'><h2>$link</h2><p class='incidental'>Click for a list of departmental graduation events</p></div>";
}

// we're done if there are no past or upcoming windows
if (!$noun->pastSignupWindows() && !$noun->upcomingSignupWindows()) {
    return;
}

echo "<div class='digraph-card incidental' style='max-width:100%;'>";

/**
 * List upcoming signup windows
 */
if ($windows = $noun->upcomingSignupWindows()) {
    echo "<h2 style='text-align:center;'>Upcoming signup windows</h2>";
    echo "<ul>";
    foreach ($windows as $w) {
        echo "<li><strong>" . $w->link() . "</strong><br>opens " . $s->dateTimeHTML($w['signupwindow.time.start']) . "</li>";
    }
    echo "</ul>";
}

/**
 * List past signup windows
 */
if ($windows = $noun->pastSignupWindows()) {
    echo "<h2 style='text-align:center;'>Past signup windows</h2>";
    echo "<ul>";
    foreach ($windows as $w) {
        echo "<li><strong>" . $w->link() . "</strong><br>closed " . $s->dateTimeHTML($w['signupwindow.time.end']) . "</li>";
    }
    echo "</ul>";
}

echo "</div>";