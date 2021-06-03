<?php

namespace Digraph\Modules\ous_event_management\Tickets;

use Digraph\DSO\Noun;
use Digraph\Forms\Fields\FileStoreFieldMulti;
use Digraph\Modules\ous_event_management\Event;
use Digraph\Modules\ous_event_management\Signup;
use Digraph\Modules\ous_event_management\SignupWindow;
use Digraph\Users\UserInterface;

class TicketGroup extends Noun
{
    protected $signupIDs;

    public function content()
    {
        return $this['content'];
    }

    public function instructions()
    {
        if (!$this['instructions']) {
            return null;
        }
        return $this->cms()->helper('filters')->filterContentField(
            $this['instructions'],
            $this['dso.id']
        );
    }

    public function actions($links)
    {
        $links = parent::actions($links);
        $links['link-signups'] = '!noun/link-signups';
        return $links;
    }

    public function signupIDs(): array
    {
        if ($this->signupIDs === null) {
            $this->signupIDs = [];
            // add signup IDs from linked events
            $events = $this->cms()->helper('graph')
                ->childIDs($this['dso.id'], 'event-ticket-group-event');
            foreach ($events as $event) {
                $this->signupIDs = $this->signupIDs + $this->cms()
                    ->helper('graph')
                    ->childIDs($event, 'event-event-signup');
            }
            // limit signups to those from given windows
            $windows = $this->cms()->helper('graph')
                ->childIDs($this['dso.id'], 'event-ticket-group-signupwindow');
            if ($windows) {
                $intersect = [];
                foreach ($windows as $window) {
                    $intersect = $intersect + $this->cms()
                        ->helper('graph')
                        ->childIDs($window, 'event-signupwindow-signup');
                }
                $this->signupIDs = array_intersect($this->signupIDs, $intersect);
            }
            // add directly-linked child signups
            $this->signupIDs = $this->signupIDs + $this->cms()
                ->helper('graph')
                ->childIDs($this['dso.id'], 'event-ticket-group-signup');
            // unique
            $this->signupIDs = array_unique($this->signupIDs);
        }
        return $this->signupIDs;
    }

    public function signupsFor(UserInterface $user): array
    {
        $search = $this->cms()->factory()->search();
        $search->where('(${signup.for} = :netid OR ${signup.owner} = :user) AND ${dso.id} in ("' . implode('","', $this->signupIDs()) . '")');
        $results = $search->execute([
            'netid' => $user->identifier(),
            'user' => $user->id()
        ]);
        $results = array_filter(
            $results,
            function (Signup $signup) {
                return $signup->complete();
            }
        );
        return $results;
    }

    public function css(): string
    {
        if (!$this['css']) {
            return '';
        }
        $css = '.event-ticket.event-ticket-group-' . $this['dso.id'] . ' {' . PHP_EOL;
        $css .= $this['css'];
        $css .= PHP_EOL . '}';
        return $this->cms()->helper('media')->prepareCSS($css);
    }

    public function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['digraph_title'] = false;
        $map['digraph_body'] = false;
        $map['instructions'] = [
            'label' => 'Instructions',
            'class' => 'digraph_content',
            'extraConstructArgs' => ['twig'],
            'field' => 'instructions',
            'weight' => 500
        ];
        $map['files'] = [
            'label' => 'Additional files',
            'class' => FileStoreFieldMulti::class,
            'extraConstructArgs' => ['files'],
            'weight' => 501
        ];
        $map['content'] = [
            'label' => 'Content template (Twig)',
            'class' => 'code',
            'extraConstructArgs' => ['twig'],
            'field' => 'content',
            'weight' => 600,
            'tips' => [
                'Enter either a complete Twig template, or the lone filename of a template in templates/event-tickets to render it.',
                'Leave blank to activate the no content mode of the ticket design.'
            ]
        ];
        $map['css'] = [
            'label' => 'Custom CSS',
            'class' => 'code',
            'extraConstructArgs' => ['css'],
            'field' => 'css',
            'weight' => 700,
            'tips' => [
                'CSS entered here will be scoped to the ID of rendered tickets, and is parsed with CSS Crush so you can nest additional rules for sub-objects.'
            ]
        ];
        return $map;
    }

    public function parentEdgeType(Noun $parent): ?string
    {
        if ($parent instanceof Event) {
            return 'event-ticket-group';
        }
        return null;
    }

    public function childEdgeType(Noun $child): ?string
    {
        if ($child instanceof Signup || $child instanceof ManualTicket) {
            return 'event-ticket-group-signup';
        }
        if ($child instanceof SignupWindow) {
            return 'event-ticket-group-signupwindow';
        }
        if ($child instanceof Event) {
            return 'event-ticket-group-event';
        }
        return null;
    }
}
