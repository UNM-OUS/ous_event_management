<?php
$package->cache_noStore();
$events = $cms->helper('events');

$name = $package['url.args.name'];
$existing = $events->mailTemplate($name);

$form = $cms->helper('forms')->form(($existing ? 'Edit' : 'Add') . ' template: ' . $name);

$form['subject'] = $cms->helper('forms')->field('text', 'Subject');
$form['subject']->required('true');

$form['body'] = $cms->helper('forms')->field('digraph_content_default', 'Body');
$form['body']->required('true');
$form['body']->extra([]);

$form->default($existing);

echo $form;

if ($form->handle()) {
    var_dump(
        $name,
        $form['subject']->value(),
        $form['body']->value()
    );
    $events->addMailTemplate(
        $name,
        $form['subject']->value(),
        $form['body']->value()
    );
    $cms->helper('notifications')->flashConfirmation('Email template updated');
    $package->redirect(
        $this->url('_event-management', 'mail-templates')
    );
}
