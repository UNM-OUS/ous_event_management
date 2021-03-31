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
 * List current signup windows
 */
if ($windows = $noun->currentSignupWindows()) {
    echo "<div class='notification notification-confirmation'>";
    foreach ($windows as $w) {
        echo "<p><strong>" . $w->link() . "</strong><br>closes " . $s->dateTimeHTML($w['signupwindow.time.end']) . "</p>";
    }
    echo "</div>";
    echo "<hr>";
}

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
    echo "<h2>Events</h2>";
    foreach ($events as $event) {
        echo "<div class='digraph-card'>";
        echo "<h2><a href='" . $event->url() . "'>" . $event->title() . "</a></h2>";
        echo '<div class="incidental">' . $event->metaCell() . '</div>';
        echo "</div>";
        echo "<hr>";
    }
}

/** display secondary events */
if ($noun->secondaryEvents()) {
    $link = $noun->link(null, 'secondary-events');
    $link->addClass('cta-button');
    $link->attr('style', 'display:block;');
    echo "<p>$link</p>";
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