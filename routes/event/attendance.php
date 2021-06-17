<?php

use Formward\Fields\Select;

$package->cache_noStore();

/** @var \Digraph\Modules\ous_event_management\Event */
$event = $package->noun();

$signupIDs = $event->allSignupIDs();
$signupIDsClause = "\${dso.id} in ('" . implode("','", $signupIDs) . "')";

$search = $cms->factory()->search();
$search->where('${signup_windowtype} = "faculty" AND ${complete.state} = "complete" AND ' . $signupIDsClause);
$search->order('${contact.lastname} asc, ${contact.firstname} asc');

$form = $cms->helper('forms')->form('');

foreach ($search->execute() as $signup) {
    $form[$signup['dso.id']] = new Select($signup->name());
    $form[$signup['dso.id']]->options([
        'true' => 'Attended',
        'false' => 'Did not attend'
    ]);
    $default = $signup->attended($event['dso.id']);
    if ($default !== null) {
        $form[$signup['dso.id']]->default($default ? 'true' : 'false');
    }
}

echo $form;

if ($form->handle()) {
    $count = 0;
    foreach ($search->execute() as $signup) {
        if ($field = $form[$signup['dso.id']]) {
            $value = $field->value();
            $value = $value ? ($value == 'true' ? true : false) : null;
            if ($value !== null) {
                $count++;
                $signup->attended($event['dso.id'], $value);
            }
        }
    }
    $cms->helper('notifications')->confirmation('Attendance records updated (' . $count . ' specified)');
}
