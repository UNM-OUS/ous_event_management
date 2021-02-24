<?php

use Digraph\CMS;
use Digraph\DSO\Noun as DSONoun;
use Digraph\Forms\Fields\Noun;
use Digraph\Modules\ous_event_management\Event;
use Digraph\Modules\ous_event_management\EventGroup;
use Digraph\Modules\ous_event_management\SignupWindow;
use Digraph\Mungers\Package;
use Formward\Fields\Ordering;

$package['response.ttl'] = 3600;

// load sources from URL, check source form for updates
$sources = array_filter(explode(',', $package['url.args.n']));
if (sourceForm($package, $cms, $sources)) {
    return;
}

// display message if there are no sources
if (!$sources) {
    $cms->helper('notifications')->printNotice('To display this report, select at least one source using the form. Reports can be run on just one, or on a combination of any number of signup windows, events, or event groups.');
    return;
}

// load signup IDs and build search
$signupIDs = signupIDs($sources, $cms);
$where = '${dso.id} in (' . implode(',', array_map(
    function ($e) {
        return '"' . $e . '"';
    },
    $signupIDs
)) . ')';
$search = $cms->factory()->search();
$search->where($where . ' AND (${moderation.state} = "approved" AND ${complete.state} = "complete")');
$search->order('${dso.modified.date} DESC');

$zip = $package['url.args.zip'];
if ($zip) {
    $zip = new ZipArchive;
    $zipName = $cms->config['paths.cache'] . '/photozip.' . md5(serialize($sources)) . '.zip';
    $zip->open($zipName, ZipArchive::CREATE);
}

$preview = '';
foreach ($search->execute() as $signup) {
    if ($page = $signup->personalizedPage()) {
        if ($photo = $page->pagePhoto()) {
            $preview .= $photo->metaCard();
            if ($zip) {
                $zip->addFile($photo->path(), $photo->name());
            }
        }
    }
}
$url = $package->url();

if ($zip) {
    // export zip file
    $zip->close();
    $package->makeMediaFile('pagephotos.' . md5($preview) . '.zip');
    $package['response.outputmode'] = 'readfile';
    $package['response.readfile'] = $zipName;
    return;
} else {
    // show preview
    $url['args.zip'] = true;
    echo "<p><a href='$url'>Download all as ZIP file</a></p>";
    echo $preview;
}

/**
 * Find all the signups located under a given array of sources
 *
 * @param array $sources
 * @param CMS $cms
 * @return array
 */
function signupIDs(array $sources, CMS $cms): array
{
    $ids = [];
    $sources = array_filter(array_map([$cms, 'read'], $sources));
    foreach ($sources as $i => $source) {
        $ids = $ids + signupIDs_single($source, $cms);
    }
    return array_values(array_unique($ids));
}

function signupIDs_single(DSONoun $source, CMS $cms): array
{
    if ($source instanceof SignupWindow) {
        return $cms->helper('graph')->childIDs($source['dso.id'], 'event-signupwindow-signup');
    } elseif ($source instanceof Event) {
        return $cms->helper('graph')->childIDs($source['dso.id'], 'event-event-signup');
    } elseif ($source instanceof EventGroup) {
        $out = [];
        foreach ($source->signupWindows() as $window) {
            $out = $out + signupIDs_single($window, $cms);
        }
        return array_unique($out);
    } else {
        return [];
    }
}

/**
 * Builds and prints a form for changing the sources of this report. Returns
 * true if sources are changed, because that means we'll redirect and further
 * execution can stop.
 *
 * @param Package $package
 * @param CMS $cms
 * @param array $sources
 * @return boolean
 */
function sourceForm(Package $package, CMS $cms, array $sources): bool
{
    $nf = $cms->helper('forms')->form('');
    $nf->csrf(false);
    $nf->submitButton()->label('Update sources');
    $nf['current'] = new Ordering('Current report sources');
    $nf['current']->allowDeletion(true);
    $opts = [];
    foreach ($sources as $id) {
        if ($noun = $cms->read($id)) {
            $opts[$id] = $noun->name() . '<div class="incidental">' . $noun->url() . '</div>';
        }
    }
    $nf['current']->opts($opts);
    $nf['add'] = new Noun('Add source');
    $nf['add']->limitTypes(['event-group', 'event', 'event-signupwindow']);

    if ($nf->handle()) {
        $sources = $nf['current']->value();
        if ($nf['add']->value()) {
            $sources[] = $nf['add']->value();
        }
        $url = $package->url();
        $url['args.n'] = implode(',', $sources);
        $package->redirect($url);
        return true;
    }

    if (!$nf['current']->value()) {
        $nf['current']->addClass('hidden');
    }
    echo $nf;
    return false;
}
