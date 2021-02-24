<?php
use Formward\Fields\CheckboxList;

$package->cache_noStore();
$signup = $package->noun();
$graph = $cms->helper('graph');

if (!$signup->allowUpdate()) {
    $package->error(403);
    return;
}

/**
 * Bounce immediately if there are no events available
 */
if (!$signup->signupWindow()->allEvents()) {
    $package->redirect($signup->url());
    return;
}

/**
 * Set and immediately bounce if there's only one event available
 */
if (count($signup->signupWindow()->allEvents()) == 1) {
    $signup->setEvents($signup->signupWindow()->allEvents());
    $package->redirect($signup->url());
    return;
}

/**
 * Initialize event selection form
 */
$form = $cms->helper('forms')->form('');
$form->submitButton()->label('Save selections');

/**
 * Primary events
 */
$primary = new CheckboxList('Main events');
if ($signup->signupWindow()->primaryEvents()) {
    $options = [];
    $default = [$package['url.args.from']];
    foreach ($signup->signupWindow()->primaryEvents(true) as $e) {
        $options[$e['dso.id']] = '<strong>' . $e->name() . '</strong><div class="incidental">' . $e->metaCell() . '</div>';
    }
    foreach ($signup->primaryEvents() as $e) {
        $default[] = $e['dso.id'];
    }
    $primary->options($options);
    $primary->default($default);
    $form['primary'] = $primary;
}

/**
 * Secondary events
 */
$secondary = new CheckboxList('Secondary events');
if ($signup->signupWindow()->secondaryEvents()) {
    $options = [];
    $default = [$package['url.args.from']];
    foreach ($signup->signupWindow()->secondaryEvents() as $e) {
        $options[$e['dso.id']] = '<strong>' . $e->name() . '</strong><div class="incidental">' . $e->metaCell() . '</div>';
    }
    foreach ($signup->secondaryEvents() as $e) {
        $default[] = $e['dso.id'];
    }
    $secondary->options($options);
    $secondary->default($default);
    $form['secondary'] = $secondary;
}

/**
 * Merge field values into single event list
 */
if ($form->handle()) {
    $allEvents = $primary->value();
    $allEvents = array_merge($allEvents, $secondary->value());
    $signup->setEvents($allEvents);
    $cms->helper('notifications')->flashConfirmation('Event selections saved');
    $package->redirect($signup->url());
    return;
}

$cms->helper('notifications')->printConfirmation('Please check the events you plan to participate in.');
echo $form;
