<?php
$noun = $package->noun();
$u = $cms->helper('users');
$s = $cms->helper('strings');
$package['response.headers.x-robots-tag'] = 'noindex';
if ($u->id()) {
    $package['response.ttl'] = 10;
} else {
    $package['response.ttl'] = 300;
}
$package['response.cache.max-age'] = 0;

/**
 * Display event body
 */
echo $noun->body();

// open wrapper for signup link and deadlines
echo "<div class='digraph-card'>";

/**
 * Locate signup for the current user
 */
if ($mySignup = $noun->findSignupFor()) {
    if ($mySignup->complete()) {
        $cms->helper('notifications')->printConfirmation(
            'Your signup is complete. <a href="' . $mySignup->url() . '">View/edit it here</a>.'
        );
    } else {
        if (time() < $noun['signupwindow.time.end']) {
            $cms->helper('notifications')->printWarning(
                'Your signup is currently incomplete. <a href="' . $mySignup->url() . '">Complete it here</a> by the signup deadline.'
            );
        } else {
            $cms->helper('notifications')->printWarning(
                'Your signup was not completed by the signup deadline. <a href="' . $mySignup->url() . '">View it here</a>.'
            );
        }
    }
}

/**
 * Create new signup link
 */
$windowOpen = (time() >= $noun['signupwindow.time.start']) && (time() <= $noun['signupwindow.time.end']);
$signupUrl = $noun->url('signup');
$signupUrl['args.from'] = $package['url.args.from'];
if ($noun->canSignUpOthers()) {
    if ($windowOpen || $noun->signupAllowed()) {
        echo "<p><a class='cta-button blue' style='text-align:center;display:block;' href='$signupUrl'>Create new signup now</a></p>";
    }
} else {
    if ($windowOpen || $noun->signupAllowed()) {
        if (!$mySignup && $noun->signupAllowed()) {
            echo "<p><a class='cta-button green' style='text-align:center;display:block;' href='$signupUrl'>Sign up now</a></p>";
        } elseif (!$u->id()) {
            echo "<p><a class='cta-button red' style='text-align:center;display:block;' href='" . $u->signInUrl($package) . "'>Sign in</a></p>";
        } elseif (!$mySignup) {
            $cms->helper('notifications')->printWarning(
                '<p>You are not on the list of users allowed to sign up through this form.</p>' .
                '<p>If you believe this is in error, please first contact your academic advisor if you are a student.</p>' .
                '<p>If you are not a student, or you have checked with your advisor and are sure your degree records are in Banner, please contact the Office of the University Secretary. To allow any problem to be investigated as quickly as possible, include your main campus NetID and the URL of this page.</p>'
            );
        }
    }
}

/**
 * display closure/deadline
 */
if (time() < $noun['signupwindow.time.start']) {
    echo "<p>Signup window opens " . $s->datetimeHTML($noun['signupwindow.time.start']) . "</p>";
}
if (time() < $noun['signupwindow.time.end']) {
    echo "<p>Signup window closes " . $s->datetimeHTML($noun['signupwindow.time.end']) . "</p>";
} else {
    echo "<p>Signup window closed " . $s->datetimeHTML($noun['signupwindow.time.end']) . "</p>";
}

// close wrapper for signup link and deadlines
echo "</div>";

/**
 * Locate current user's owned signups
 */
if ($signups = $noun->signupsByOwner()) {
    // only display this section if they have at least one signup that isn't their own
    if (!$mySignup || count($signups) > 1) {
        echo "<div class='digraph-card'>";
        echo "<h2>Owned signups</h2>";
        echo "<p>You are the owner of the following signups, and are allowed to view/edit them:</p>";
        echo "<ul>";
        foreach ($signups as $s) {
            echo "<li>" . $s['signup.for'] . ": " . $s->link() . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}
