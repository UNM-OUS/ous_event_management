<?php

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Digraph\Modules\event_attendance\ManualTicket;
use Digraph\Modules\ous_event_management\Signup;

$package->cache_noStore();
$package['response.template'] = 'content-only.twig';

// require arg
if (!$package['url.args.s']) {
    $package->error(404);
    return;
}

/** @var \Digraph\Templates\NotificationsHelper */
$n = $cms->helper('notifications');
/** @var \Digraph\Modules\event_attendance\TicketGroup */
$group = $package->noun();
/** @var \Digraph\Modules\event_regalia\Signup */
$signup = $cms->read($package['url.args.s'], false);

// require signup
if (!$signup) {
    $package->error(404);
    return;
}

// check permissions if signup isn't "for" an email address
if ($signup instanceof Signup) {
    // signup must exist
    if (!$signup || !$signup->complete()) {
        $n->printError('Ticket not found');
        return;
    }
    // signup object
    if (strpos($signup['signup.for'], '@') !== false) {
        if (!$signup->allowViewing()) {
            $n->printError('Not allowed to view this signup');
            return;
        }
    }
} elseif ($signup instanceof ManualTicket) {
    // manual ticket
} else {
    // other types aren't allowed
    throw new \Exception("Linked signup isn't a Signup or ManualTicket");
}

// template args
$args = [
    'parent' => $group->parent(),
    'signup' => $signup,
    'group' => $group,
    'ticket_id' => $group['dso.id'] . '/' . $signup['dso.id']
];

// qr code
$qr = [
    's' => $signup['dso.id'],
    'g' => $group['dso.id']
];
$renderer = new ImageRenderer(
    new RendererStyle(800, 0),
    new SvgImageBackEnd()
);
$writer = new Writer($renderer);
$args['ticket_qr'] = $writer->writeString(json_encode($qr));

// ticket content
/** @var \Digraph\Templates\TemplateHelper */
$templates = $cms->helper('templates');
$content = $group->content();
if ($templates->exists('event-tickets/' . $content)) {
    $content = $templates->render('event-tickets/' . $content, $args);
} else {
    $content = $templates->renderString($content, $args);
}
$args['ticket_content'] = $content;
$args['ticket_no_content'] = !trim($content);

// ticket instructions
$args['ticket_instructions'] = $group->instructions();

// render template (and print button)
echo "<a href='#' onclick='window.print();return false;' class='cta-button noprint' style='display:block;max-width:10em;margin:0 auto;'><i class='fa fa-print'></i> Print</a>";
echo $templates->render('event-tickets/default.twig', $args);

?>
<style>
    article>h1:first-child {
        display: none;
    }

    @media print {

        #digraph-loboalerts,
        #digraph-unm,
        #digraph-xsitenav,
        #digraph-masthead,
        #digraph-actionbar,
        #digraph-navbar,
        #digraph-breadcrumb,
        #digraph-notifications,
        #digraph-hero,
        #digraph-footer,
        #digraph-debug-dump,
        .noprint {
            display: none !important;
        }
    }
</style>