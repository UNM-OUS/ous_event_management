<?php

use Digraph\Forms\Form;
use Formward\Fields\CheckboxList;
use Formward\Fields\Select;

$package->cache_noStore();

/** @var \Digraph\Modules\ous_event_management\SignupWindow */
$window = $package->noun();

$form = new Form('Report settings');
$form['preset'] = new Select('Report type');
$form['preset']->required(true);
$form['events'] = new CheckboxList('Events to include');
$form['events']->addTip('Leave blank to include all');

/**
 * set up options from events
 */
$eventsOptions = [];
foreach ($window->eventGroup()->primaryEvents() + $window->eventGroup()->secondaryEvents() as $w) {
    $eventsOptions[$w['dso.id']] = $w->name();
}
$form['events']->options($eventsOptions);

/**
 * Set up options from presets
 */
$reportPresets = $cms->helper('events')->reportDataStore()->getAll();
uasort(
    $reportPresets,
    function ($a, $b) {
        return strcasecmp($a['t'], $b['t']);
    }
);
$form['preset']->options(array_map(
    function ($e) {
        return $e['t'];
    },
    $reportPresets
));

if ($form->handle()) {
    $url = $cms->helper('urls')->parse('_event-management/report');
    $url['args.preset'] = $form['preset']->value();
    $url['args.events'] = implode(',', $form['events']->value());
    $url['args.windows'] = $window['dso.id'];
    $url['args.parent'] = $window['dso.id'];
    $package->redirect($url);
    return;
}

echo $form;
