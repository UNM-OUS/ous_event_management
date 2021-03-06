<?php

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Digraph\CMS;
use Digraph\DSO\Noun as DSONoun;
use Digraph\Modules\ous_event_management\Event;
use Digraph\Modules\ous_event_management\EventGroup;
use Digraph\Modules\ous_event_management\Signup;
use Digraph\Modules\ous_event_management\SignupWindow;

$package['response.ttl'] = 3600;
// $package->cache_noStore();

// load up preset
if ($package['url.args.preset']) {
    // using a preset, pull its settings from datastore
    $r = $cms->helper('events')->reportDataStore()->get($package['url.args.preset']);
} else {
    // not using a preset, pull settings from URL
    // pull settings from args
    $r = $package['url.args.r'];
    if (!($r = $package->url()->getData())) {
        $cms->helper('notifications')->printError('No report settings');
        return;
    }
}

// set up parent
if ($package['url.args.parent'] && $parent = $cms->read($package['url.args.parent'])) {
    $package->overrideParent($parent->url('reports'));
} else {
    $parent = $cms->helper('urls')->parse('_event-management/reportbuilder');
    $parent->setData($r);
    $package->overrideParent($parent);
}

// set page title
$package['fields.page_name'] = "Report: " . $r['t'];

// load signup IDs
$events = array_filter(array_map([$cms, 'read'], explode(',', $package['url.args.events'])));
$windows = array_filter(array_map([$cms, 'read'], explode(',', $package['url.args.windows'])));
$signupIDs = signupIDs($events, $windows, $cms);

// set up where clause for IDs
$where = '${dso.id} in (' . implode(',', array_map(
    function ($e) {
        return '"' . $e . '"';
    },
    $signupIDs
)) . ')';

// add report SQL to query
$where .= ' AND (' . $r['f'] . ')';

// add signup_type to query
$types = array_filter(array_map(function ($e) {
    return preg_replace('/[^a-z ]/', '', $e);
}, explode(',', $package['url.args.types'])));
if ($types) {
    $where = '(' . $where . ') AND ${signup_windowtype} in (' . implode(',', array_map(
        function ($e) {
            return '"' . $e . '"';
        },
        $types
    )) . ')';
}

// set up search
$search = $cms->factory()->search();
$search->where($where);
$count = $search->count();

// display stats about searches
$completed = $cms->factory()->search();
$completed->where('(' . $where . ') AND ${complete.state} = "complete"');
echo "<ul class='incidental'>";
if ($events) {
    echo "<li>Included events<ul>";
    foreach ($events as $source) {
        echo "<li>" . $source->link() . "</li>";
    }
    echo "</ul></li>";
}
if ($windows) {
    echo "<li>Included signup windows<ul>";
    foreach ($windows as $source) {
        echo "<li>" . $source->link() . "</li>";
    }
    echo "</ul></li>";
}
if ($types) {
    echo "<li>limited to type: " . implode(', ', $types) . "</li>";
}
echo "</ul>";
echo "<p class='incidental'>";
echo "Specified sources signup total: " . count($signupIDs);
echo "<br>Search results: $count displayed of " . $completed->count() . " completed";
echo "<br>Generated " . $cms->helper('strings')->datetimeHTML();
echo '</p>';

// parse requested columns
$columns = array_filter(array_map(
    function ($e) {
        if (preg_match('/^(.+):(.+?)(\|(.+))?$/', $e, $m)) {
            $fn = null;
            if (@$m[4]) {
                $fn = 'format_cell_' . $m[4];
                if (!function_exists($fn)) {
                    $fn = null;
                }
            }
            return [
                'title' => $m[1],
                'src' => $m[2],
                'fmt' => $fn,
            ];
        } else {
            return false;
        }
    },
    preg_split('/[\r\n]+/', $r['c'])
));

// construct values
$search->order($r['s']);
$hash = md5('');
$results = array_map(
    function (Signup $signup) use ($cms, $columns, &$hash) {
        $row = [];
        foreach ($columns as $c) {
            $value = '';
            if (substr($c['src'], -2) == '()') {
                $m = substr($c['src'], 0, strlen($c['src']) - 2);
                if (method_exists($signup, $m)) {
                    $value = call_user_func([$signup, $m]);
                } else {
                    $value = 'function ' . $m . ' not found';
                }
            } else {
                $value = $signup[$c['src']];
            }
            if ($fn = $c['fmt']) {
                $value = $fn($value, $signup, $cms);
            }
            $row[] = $value;
        }
        $row = array_map(
            function ($c) {
                if (is_array($c)) {
                    $c[0] = strval($c[0]);
                    $c[1] = strval($c[1]);
                } else {
                    $c = strval($c);
                }
                return $c;
            },
            $row
        );
        $hash = md5($hash . serialize($row));
        return $row;
    },
    $search->execute()
);

// create spreadsheet
/** @var \Digraph\Media\MediaHelper */
$media = $cms->helper('media');
$asset = $media->create(
    $r['t'] . ' ' . crc32(serialize($r)) . '_' . date('YmdHi') . '.xlsx',
    function ($dest) use ($cms, $columns, &$results) {
        $writer = WriterEntityFactory::createXLSXWriter();
        $cms->helper('filesystem')->put('', $dest, true);
        $writer->openToFile($dest);
        // create first row
        $writer->addRow(
            WriterEntityFactory::createRow(
                array_map(
                    function ($c) {
                        return WriterEntityFactory::createCell($c['title']);
                    },
                    $columns
                )
            )
        );
        // use results to build the rest
        foreach ($results as $row) {
            $writer->addRow(
                WriterEntityFactory::createRow(
                    array_map(
                        function ($c) {
                            if (is_array($c)) {
                                $v = strval($c[1]);
                            } else {
                                $v = strval($c);
                            }
                            return WriterEntityFactory::createCell($v);
                        },
                        $row
                    )
                )
            );
        }
        // close file
        $writer->close();
    },
    $hash
);
echo '<p>Download: <a href="' . $asset['url'] . '">' . $asset['filename'] . '</a></p>';

// print table header
echo "<table><tr>";
foreach ($columns as $c) {
    echo "<th>{$c['title']}</th>";
}
echo "</tr>";
// print table
foreach ($results as $row) {
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td>";
        if (is_array($value)) {
            echo $value[0];
        } else {
            echo $value;
        }
        echo "</td>";
    }
    echo "</tr>";
}
echo "</table>";

function format_cell_date($value, Signup $signup, CMS $cms): array
{
    return [$cms->helper('strings')->dateHTML($value), date("Y-m-d H:i:s", $value)];
}

function format_cell_link($value, Signup $signup, CMS $cms): array
{
    return [$signup->link(), $signup->url()->__toString()];
}

function format_cell_uniqid($value, Signup $signup, CMS $cms): array
{
    return [$signup->link($signup['dso.id']), $signup['dso.id']];
}

function signupIDs(array $events, array $windows, CMS $cms): array
{
    $eventIDs = [];
    foreach ($events as $source) {
        $eventIDs = $eventIDs + signupIDs_single($source, $cms);
    }
    $windowIDs = [];
    foreach ($windows as $source) {
        $windowIDs = $windowIDs + signupIDs_single($source, $cms);
    }
    $eventIDs = array_unique($eventIDs);
    $windowIDs = array_unique($windowIDs);
    if ($events && $windows) {
        $ids = array_intersect($eventIDs, $windowIDs);
    } else {
        $ids = array_unique($eventIDs + $windowIDs);
    }
    return array_values($ids);
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
