<?php

use Digraph\Forms\Fields\Noun;
use Digraph\Modules\ous_event_management\Event;
use Digraph\Modules\ous_event_management\SignupWindow;
use Formward\Fields\Checkbox;
use Formward\Fields\Input;
use Formward\Fields\Textarea;

$package->cache_noStore();

$form = $cms->helper('forms')->form('');

$form['title'] = new Input('Report title');
$form['title']->required(true);

$form['save'] = new Checkbox('Save this report as a preset');

$form['filter'] = new Textarea('Filtering');
$form['filter']->default(implode(PHP_EOL, [
    '${complete.state} = "complete"',
]));

$form['sort'] = new Input('Sorting');
$form['sort']->default(implode(PHP_EOL, [
    '${dso.modified.date} ASC',
]));

$form['columns'] = new Textarea('Columns to display');
$form['columns']->default(implode(PHP_EOL, [
    'Signup Link:link()',
    'Created:dso.created.date|date',
    'Modified:dso.modified.date|date',
]));

$form['noun'] = new Noun('Event or signup window');
$form['noun']->required(true);
$form['noun']->limitTypes(['convocation', 'event', 'event-signupwindow']);

/**
 * pull form defaults from URL if necessary
 */
// pull settings from args
if ($r = $package->url()->getData()) {
    $package->requireUrlHash();
    $form['filter']->default($r['f']);
    $form['title']->default($r['t']);
    $form['sort']->default($r['s']);
    $form['columns']->default($r['c']);
}

/**
 * handle form and bounce to reports
 */
if ($form->handle()) {
    $url = $cms->helper('urls')->parse('_event-management/report');
    // turn $form['noun'] into args.events or args.windows
    $src = $cms->read($form['noun']->value());
    if ($src instanceof Event) {
        $url['args.events'] = $form['noun']->value();
    } elseif ($src instanceof SignupWindow) {
        $url['args.windows'] = $form['noun']->value();
    }
    // behavior varies depending on whether preset is being saved
    if ($form['save']->value()) {
        // save preset and redirect to it
        $id = $cms->helper('events')->saveReportPreset(
            $form['title']->value(),
            $form['filter']->value(),
            $form['sort']->value(),
            $form['columns']->value()
        );
        $url['args.preset'] = $id;
    } else {
        // make a link without using a preset ID
        $url->setData([
            't' => $form['title']->value(),
            'f' => $form['filter']->value(),
            's' => $form['sort']->value(),
            'c' => $form['columns']->value(),
        ]);
        $cms->helper('urls')->hash($url);
    }
    // redirect to generated URL
    $package->redirect($url);
}

echo $form;
