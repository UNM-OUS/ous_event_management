<?php

use Digraph\Forms\Fields\Noun;
use Digraph\Modules\event_attendance\ManualTicket;
use Digraph\Modules\ous_event_management\Signup;

$package->cache_noStore();

/* handle edge removals */
if (($data = $package->url()->getData()) && $data['time'] + 3600 > time()) {
    /** @var \Digraph\Graph\EdgeHelper */
    $edges = $cms->helper('edges');
    $edges->delete($package['noun.dso.id'], $data['child']);
    $package->redirect($package->noun()->url('link-signups'));
    return;
}

/* Form for adding edges */
$form = $cms->helper('forms')->form('');
$form['noun'] = new Noun('Select object to link', null, null, $cms);
$form['noun']->limitTypes(['event-signup', 'event', 'event-signupwindow', 'convocation']);
$form['noun']->addTip('Add an event here to automatically allow tickets for all its completed signups');
$form['noun']->addTip('Add a signup window to <strong>limit</strong> signups from the given events to one or more signup windows');
$form['noun']->addTip('Add a single signup to either pull it in from another source, or to add a link to its ticket to the list below for your own convenience');
$form['noun']->required(true);
echo $form;
if ($form->handle()) {
    $cms->helper('edges')->create($package['noun.dso.id'], $form['noun']->value());
    $package->redirect($package->url());
    return;
}

/** @var \Digraph\Graph\GraphHelper */
$graph = $cms->helper('graph');
$links = ['All signups from' => $graph->children(($package['noun.dso.id']), 'event-ticket-group-event')];
$links['Limited to signup windows'] = $graph->children(($package['noun.dso.id']), 'event-ticket-group-signupwindow');
$links['Manually specified individuals'] = $graph->children(($package['noun.dso.id']), 'event-ticket-group-signup');

echo "<h2>Link signups from</h2>";
foreach ($links as $section => $slinks) {
    if (!$slinks) {
        continue;
    }
    echo "<h3>$section</h3>";
    echo "<ul>";
    foreach ($slinks as $link) {
        $ticket = '';
        if ($link instanceof Signup || $link instanceof ManualTicket) {
            $ticket = $package->noun()->url('ticket', ['s' => $link['dso.id']]);
            $ticket = "[<a href='$ticket'>ticket</a>]";
        }
        if ($link instanceof ManualTicket) {
            $remove = '';
        } else {
            $url = $package->url();
            $url->setData([
                'time' => time(),
                'child' => $link['dso.id']
            ]);
            $remove = "[<a href='$url'>remove</a>]";
        }
        echo "<li>" . $link->link() . " $ticket $remove</li>";
    }
    echo "</ul>";
}
