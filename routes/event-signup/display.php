<?php
$package->cache_noStore();
$signup = $package->noun();
$package['response.headers.x-robots-tag'] = 'noindex';

if (!$signup->allowViewing()) {
    $package->error(403);
    return;
}

echo "<noscript><div class='notification notification-warning'>Enabling Javascript on this page is highly recommended. The user experience is greatly reduced with Javascript turned off.</div></noscript>";
echo '<div id="signup-status-wrapper"><div id="signup-status" class="notification notification-' . ($signup->complete() ? 'confirmation' : 'warning') . '">';
echo $signup->complete() ? 'This signup is complete' : 'This signup is incomplete';
echo '</div></div>';

/**
 * Signed up events list
 */
$events = $signup->allEvents();
$windowEvents = $signup->signupWindow() ? $signup->signupWindow()->allEvents() : [];
if ($events || $windowEvents) {
    echo "<div class='signup-chunk editing'>";
    echo "<div class='signup-chunk-label'>Events</div>";
    // verify events
    foreach ($events as $e) {
        $error = false;
        if (!in_array($e, $windowEvents)) {
            $cms->helper('notifications')->printError(
                'This signup indicates attendance for ' . $e->link() . ' which is no longer associated with this signup form.'
            );
        }
        if ($error && $signup->allowUpdate()) {
            echo "<p><a href='" . $signup->url('event-selection') . "'>Please update event selections</a></p>";
        }
    }
    // display event list
    if (!$events) {
        $cms->helper('notifications')->printWarning('No events selected');
    }
    foreach ($events as $event) {
        if ($event['cancelled']) {
            echo '<p class="notification notification-warning"><strong>' . $event->link() . '</strong><br>CANCELLED</p>';
        } else {
            echo '<p><strong>' . $event->name() . '</strong><div class="incidental">' . $event->metaCell() . '</div></p>';
        }
    }
    // display button to change event selections
    if ($signup->allowUpdate() && (count($windowEvents) > 1 || count($events) == 0)) {
        echo "<a href='" . $signup->url('event-selection') . "'>Change event selections</a>";
    }
    echo "</div>";
}

/**
 * Chunks
 */
foreach ($signup->chunks() as $chunk) {
    echo $chunk->body();
}

?>

<script>
    $(() => {
        // status checker
        var $status = $("#signup-status");
        var updateStatus = _.debounce(
            function() {
                $status.addClass('loading');
                digraph.getJSON(
                    "<?php echo $signup['dso.id']; ?>/status",
                    function (status) {
                        // update status bar
                        $status
                            .removeClass('loading')
                            .addClass('notification')
                            .removeClass('notification-warning')
                            .removeClass('notification-error')
                            .removeClass('notification-info')
                            .removeClass('notification-confirmation')
                            .addClass('notification-'+status.type)
                            .html(status.message);
                        // update titles in header and breadcrumb
                        $('article.type_event-signup>h1:first-child').html(status.name);
                        $('#digraph-meta .breadcrumb .breadcrumb-item:last-child a').html(status.name);
                    }
                );
            },
            1000
        );
        updateStatus();
        // signup chunk swapper
        $('.signup-chunk a.mode-switch').click((e) => {
            var $target = $(e.target);
            var $wrapper = $target.closest('.signup-chunk');
            var url = $target.attr('href')+'&iframe=1';
            var $chunk = $(
                '<iframe class="embedded-iframe resized" src=' +
                url +
                ' style="height:' + $wrapper.height() + 'px"></iframe>'
            );
            $chunk.on('load',updateStatus);
            $wrapper.replaceWith($chunk);
            e.preventDefault();
            return false;
        });
        // make status sticky
        var $wrapper = $('#signup-status-wrapper');
        // $('#digraph-meta > .digraph-area-wrapper').append($wrapper);
        var doSticky = function() {
            var sticky = ($(window).scrollTop()-$wrapper.offset().top) >= 0;
            if (sticky) {
                if (!$status.is('.sticky')) {
                    $wrapper.height($wrapper.height());
                }
                $status.addClass('sticky');
            }else {
                $status.removeClass('sticky');
                $wrapper.height('auto');
            }
        };
        doSticky();
        $(window).scroll(doSticky);
        $(window).resize(doSticky);
    });
</script>
<style>
    #signup-status {
        position: relative;
        text-align: center;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0;
    }
    #signup-status.sticky {
        position:fixed;
        margin:0;
        left:0;
        right:0;
        top:0;
        border-radius: 0;
        z-index: 100;
    }
    #signup-status.notification {
        transition: opacity 0.5s ease-in-out;
    }
    #signup-status.notification:after {
        content: "\f021";
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        display: block;
        position: absolute;
        top: 0;
        right: 0;
        font-size: 1rem;
        line-height: 1rem;
        padding: 0.5rem;
        opacity: 0;
        transition: opacity 0.5s ease-in-out;
    }
    #signup-status.notification.loading {
        opacity: 0.85;
    }
    #signup-status.notification.loading:after {
        opacity: 1;
    }
</style>
