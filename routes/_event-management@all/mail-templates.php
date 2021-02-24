<?php
$package->cache_noStore();

$form_e = $cms->helper('forms')->form('Edit existing template');
$form_e['name'] = $cms->helper('forms')->field('select', '');
$options = $cms->config['events.email.named_templates'];
foreach ($cms->helper('events')->mailTemplates() ?? [] as $name => $template) {
    if (!isset($options[$name])) {
        $options[$name] = $name . ': ' . $template['subject'];
    }
}
$form_e['name']->options($options);
$form_e['name']->required(true);
echo $form_e;

// echo "<hr>";

// $form_n = $cms->helper('forms')->form('Create new template');
// $form_n['name'] = $cms->helper('forms')->field('text', '');
// $form_n['name']->required(true);
// $form_n['name']->addValidatorFunction('template_name', function ($field) {
//     if ($v = $field->value()) {
//         if (!preg_match('/[a-z]+(_[a-z]+)*/', $v)) {
//             return "Invalid template name. Must be letters and underscores and start/end with a letter.";
//         }
//     }
//     return true;
// });
// echo $form_n;

if ($form_e->handle()) {
    doRedirect($form_e['name']->value(), $cms, $package);
}

// if ($form_n->handle()) {
//     doRedirect($form_n['name']->value(), $cms, $package);
// }

function doRedirect($name, $cms, $package)
{
    $package->redirect(
        $cms->helper('urls')->url('_event-management', 'mail-templates-edit', ['name' => $name])
    );
}
