<?php
$package->cache_noStore();
$signup = $package->noun();
$package['fields.page_title'] = '';

if (!$signup->allowUpdate()) {
    $package->error(403);
    return;
}

if ($package['url.args.iframe']) {
    $package['response.template'] = 'iframe.twig';
}

$chunk = $signup->chunks()[$package['url.args.chunk']];
if (!$chunk) {
    $package->error(404);
    return;
}

echo $chunk->body($package['url.args.edit'], $package['url.args.iframe']);
if ($chunk->form()->handle()) {
    $url = $package->url();
    unset($url['args.edit']);
    $package->redirect($url);
    return;
}
