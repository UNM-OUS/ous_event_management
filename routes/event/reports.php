<?php

use Digraph\Forms\Form;
use Formward\Fields\CheckboxList;
use Formward\Fields\Select;

$package->cache_noStore();

/** @var \Digraph\Modules\ous_event_management\Event */
$event = $package->noun();

$form = new Form('Report settings');
$form['preset'] = new Select('Report type');
$form['preset']->required(true);
$form['windows'] = new CheckboxList('Signup windows to include');
$form['windows']->addTip('Leave blank to include all');
$form['types'] = new CheckboxList('Limit to specific types/audiences');
$form['types']->addTip('Leave blank to include all');

/**
 * set up options from signup windows
 */
$windowsOptions = [];
$typesOptions = [];
foreach ($event->signupWindows() as $window) {
    $windowsOptions[$window['dso.id']] = $window->name();
    if ($window['signup_windowtype']) {
        $typesOptions[$window['signup_windowtype']] = $window['signup_windowtype'];
    }
}
$form['windows']->options($windowsOptions);
$form['types']->options($typesOptions);

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
    $url['args.windows'] = implode(',', $form['windows']->value());
    $url['args.types'] = implode(',', $form['types']->value());
    $url['args.events'] = $event['dso.id'];
    $url['args.parent'] = $event['dso.id'];
    $package->redirect($url);
    return;
}

echo $form;
