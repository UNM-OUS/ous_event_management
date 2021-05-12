<?php
use Digraph\Modules\ous_digraph_module\Fields\EmailOrNetID;

$package->cache_noStore();
$noun = $package->noun();
$f = $cms->helper('forms');
$u = $cms->helper('users');

/**
 * check permissions
 */
if (!$noun->signupAllowed()) {
    $url = $noun->url();
    $url['args.from'] = $package['url.args.from'];
    $package->redirect($url);
    return;
}

/**
 * Determine parameters
 */
$canSignupOthers = $noun->canSignupOthers();

/**
 * Set up form object
 */
$form = $f->form('');

/**
 * fields for who the form is about
 */
if (!$canSignupOthers) {
    $signupFor = $u->userIdentifier();
} else {
    $form['for'] = new EmailOrNetID('Who is this signup for?');
    $form['for']->addTip('Make sure this value is correct. It is used to send confirmation emails and determine who is allowed to edit/cancel this signup.');
    $form['for']->addTip('NetIDs are preferred, because they allow the user to modify their own signup. Signups made using email addresses will only be editable by you.');
    $form['for']->required(true);
    $signupFor = $form['for']->value();
}

/**
 * start signup, save it, and redirect to it to complete it
 * this happens either when the form is submitted, or when the form's
 * 'for' and 'events' fields both don't exist, indicating that no
 * options were available.
 */
if (!$form['for'] || $form->handle()) {
    if ($signup = $noun->findSignupFor($signupFor)) {
        // signup found for this user
        // in this case we bypass the event selection page
        $cms->helper('notifications')->flashNotice('An existing signup was found for this user, please see below');
        $package->redirect($signup->url());
        return;
    } else {
        // create a new signup
        $signup = $noun->createSignup($signupFor);
        $events = $noun->eventGroup()->allEvents();
        if (count($events) < 2) {
            // automatically add events if there are less than two events
            // in this case we bypass the event selection page
            $signup->setEvents($events);
            $signup->insert();
            $package->redirect($signup->url());
            return;
        }
    }
    // redirect to event selection page and return
    $signup->insert();
    $url = $signup->url('event-selection');
    $url['args.from'] = $package['url.args.from'];
    $package->redirect($url);
    return;
}

/**
 * output form
 */
echo $form;
