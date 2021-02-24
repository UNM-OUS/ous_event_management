<?php
namespace Digraph\Modules\ous_event_management;

use Digraph\DSO\Noun;
use Digraph\Forms\Fields\FieldValueAutocomplete;
use Digraph\Urls\Url;
use Formward\Fields\Input;

/**
 * This is the primary event type, and objects of this type will be given
 * top billing on group pages.
 *
 * Other, maybe user-editable event types should extend this class.
 */
class Event extends Noun
{
    const ROUTING_NOUNS = ['event'];
    const SLUG_ENABLED = true;
    const PRIMARY_EVENT = true;
    protected $eventGroup;
    protected $signupWindows;

    /**
     * Used to allow events to be placed into categories on event-list pages.
     * Returns null to be in the default/no category, at the top.
     *
     * @return string|null
     */
    public function category(): ?string
    {
        return null;
    }

    /**
     * List all the signup windows that are in the same grouping as this
     * event. Used mostly to put signup window links onto event pages.
     *
     * @return array
     */
    public function signupWindows(): array
    {
        if ($this->signupWindows === null) {
            $this->signupWindows = [];
            //add event group signup windows with same grouping
            if ($this->eventGroup()) {
                foreach ($this->eventGroup()->signupWindows() as $window) {
                    if ($window['signup_grouping'] == $this['signup_grouping']) {
                        $this->signupWindows[] = $window;
                    }
                }
            }
        }
        return $this->signupWindows;
    }

    /**
     * Link URL to be used for lists of events, in case an event is online-only
     * or something and should link to an outside website.
     *
     * @return Url
     */
    public function linkUrl(): Url
    {
        return $this->url();
    }

    /**
     * String of metadata, to be used in meta cards, and in the cell alongside
     * lists of events.
     *
     * @return string
     */
    public function metaCell(): string
    {
        $out = '';
        foreach ($this->metaItems() as $k => $v) {
            if ($k == '_more') {
                $out .= "<div>$v</div>";
            } else {
                $out .= "<div>$k: $v</div>";
            }
        }
        return $out;
    }

    public function chunks(): array
    {
        if ($p = $this['event.form.preset']) {
            if ($c = $this->cms()->config['events.forms.presets.' . $p]) {
                $chunks = $c;
            }
        }
        return [];
    }

    public function eventGroup(): ?EventGroup
    {
        if ($this->eventGroup === null) {
            $this->eventGroup = $this->cms()->helper('graph')->nearest(
                $this['dso.id'],
                function ($e) {
                    return $e instanceof EventGroup;
                }
            );
        }
        return $this->eventGroup;
    }

    public function parentEdgeType(Noun $parent): ?string
    {
        if ($parent['dso.type'] == 'event-group') {
            return 'event-group-event';
        }
        return null;
    }

    /**
     * Return whether this event is currently accepting signups,
     * may return false if, for example, disable signups or
     * cancelled are checked in the settings.
     *
     * @return boolean
     */
    public function signupsAllowed(): bool
    {
        if ($this['disablesignups'] || $this['cancelled']) {
            return false;
        }
        return true;
    }

    public function metaBlock(): string
    {
        if (!$this->metaItems()) {
            return '';
        }
        $out = "<div class='digraph-card incidental'>";
        $out .= $this->metaCell();
        $out .= "</div>";
        return $out;
    }

    protected function metaItems(): array
    {
        $items = [];
        if ($this['event.time']) {
            $items['Date/Time'] = $this->cms()->helper('strings')->datetimeHTML($this['event.time']);
        }
        if ($this['location']) {
            $items['Location'] = htmlspecialchars($this['location']);
        }
        if ($this['additionalinfo']) {
            $items['_more'] = htmlspecialchars($this['additionalinfo']);
        }
        return $items;
    }

    public function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['event_time'] = [
            'label' => 'Event time',
            'field' => 'event.time',
            'class' => 'datetime',
            'weight' => 300,
            'tips' => [
                'Can be left blank, if event is something like an online recognition website that doesn\'t occur at a specific time.',
            ],
        ];
        $map['location'] = [
            'label' => 'Event location',
            'field' => 'location',
            'class' => FieldValueAutocomplete::class,
            'extraConstructArgs' => [
                ['convocation'], //types
                ['location'], //fields,
                true, //allow adding new values
            ],
            'tips' => [
                'Leave this blank for online-only events.',
            ],
            'weight' => 300,
        ];
        $map['info'] = [
            'label' => 'Additional information',
            'field' => 'additionalinfo',
            'class' => Input::class,
            'tips' => [
                'You can use this field to add one extra line of text most places where the date/location would be displayed.',
            ],
            'weight' => 300,
        ];
        $map['event_form_preset'] = [
            'label' => 'Use preset form type',
            'field' => 'event.form.preset',
            'class' => 'fieldvalue',
            'weight' => 210,
            'required' => false,
            'extraConstructArgs' => [
                ['event'],
                ['event.form.preset'],
                $this->cms()->helper('permissions')->check('form/newpreset', 'events'),
            ],
        ];
        if (in_array('editor', $this->cms()->helper('users')->groups())) {
            $map['signup_grouping'] = [
                'label' => 'Signup grouping',
                'field' => 'signup_grouping',
                'class' => 'fieldvalue',
                'weight' => 900,
                'required' => true,
                'default' => 'default',
                'tips' => [
                    'Editor-only field',
                    'Groupings can be used to limit signup windows to using only certain events. Signup windows will only allow signing up for events that share the same grouping value.',
                ],
                'extraConstructArgs' => [
                    ['event', 'event-signupwindow'],
                    ['signup_grouping'],
                    $this->cms()->helper('permissions')->check('form/newgrouping', 'events'),
                ],
            ];
            $map['disable'] = [
                'label' => 'Disable signups',
                'field' => 'signup_disable',
                'class' => 'checkbox',
                'tips' => [
                    'Editor-only field',
                    'If you are running your own signup system and do not need to use ours, check this box to hide this event from users\' event selection options.',
                    'Note that disabling signups only prevents future signups through our site, and does not remove existing ones.',
                ],
                'weight' => 900,
            ];
            $map['cancel'] = [
                'label' => 'Cancel event',
                'field' => 'cancelled',
                'class' => 'checkbox',
                'tips' => [
                    'Editor-only field',
                    'If you have cancelled your event, indicate so here. It will be removed from the user event selections form and have a banner indicating that it is cancelled added to its landing page.',
                    'Note that cancelling only prevents future signups through our site, and does not remove existing ones.',
                ],
                'weight' => 900,
            ];
        }
        return $map;
    }
}
