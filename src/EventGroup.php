<?php
namespace Digraph\Modules\ous_event_management;

use Digraph\Modules\CoreTypes\Page;

class EventGroup extends Page
{
    const SLUG_ENABLED = true;
    const FILESTORE = true;

    protected $allEvents;
    protected $signupWindows;
    protected $billingIndex;

    public function billingIndex(): BillingIndex
    {
        if ($this->billingIndex === null) {
            if ($indexes = $this->cms()->helper('graph')->children('event-billing-index')) {
                $this->billingIndex = end($indexes);
            } else {
                $this->billingIndex = false;
            }
        }
        return $this->billingIndex;
    }

    public function chunks(): array
    {
        if ($p = $this['eventgroup.form.preset']) {
            if ($c = $this->cms()->config['events.forms.presets.' . $p]) {
                return $c;
            }
        }
        return [];
    }

    public function currentSignupWindows($unlisted = false): array
    {
        return array_filter(
            $this->signupWindows(),
            function ($e) use ($unlisted) {
                if (!$unlisted && $e['signupwindow.unlisted']) {
                    return false;
                }
                return (time() > $e['signupwindow.time.start']) && (time() < $e['signupwindow.time.end']);
            }
        );
    }

    public function pastSignupWindows($unlisted = false): array
    {
        return array_filter(
            $this->signupWindows(),
            function ($e) use ($unlisted) {
                if (!$unlisted && $e['signupwindow.unlisted']) {
                    return false;
                }
                return time() > $e['signupwindow.time.end'];
            }
        );
    }

    public function upcomingSignupWindows($unlisted = false): array
    {
        return array_filter(
            $this->signupWindows(),
            function ($e) use ($unlisted) {
                if (!$unlisted && $e['signupwindow.unlisted']) {
                    return false;
                }
                return time() < $e['signupwindow.time.start'];
            }
        );
    }

    public function signupWindows(): array
    {
        if ($this->signupWindows === null) {
            $this->signupWindows = $this->cms()->helper('graph')->children(
                $this['dso.id'],
                'event-group-signupwindow',
                1,
                '${signupwindow.time.end} ASC'
            );
        }
        return $this->signupWindows;
    }

    /**
     * Return the primary, OUS-managed events for this group
     *
     * @param boolean $signupsAllowedOnly
     * @return array
     */
    public function primaryEvents($signupsAllowedOnly = false): array
    {
        return array_filter(
            $this->allEvents($signupsAllowedOnly),
            function ($e) use ($signupsAllowedOnly) {
                return $e::PRIMARY_EVENT;
            }
        );
    }

    /**
     * Return the secondary events for this group
     *
     * @param boolean $signupsAllowedOnly
     * @return array
     */
    public function secondaryEvents($signupsAllowedOnly = false): array
    {
        return array_filter(
            $this->allEvents($signupsAllowedOnly),
            function ($e) use ($signupsAllowedOnly) {
                return !$e::PRIMARY_EVENT;
            }
        );
    }

    /**
     * Return all events of all types
     *
     * @param boolean $signupsAllowedOnly
     * @return array
     */
    public function allEvents($signupsAllowedOnly = false): array
    {
        if ($this->allEvents === null) {
            $this->allEvents = $this->cms()->helper('graph')->children($this['dso.id'], 'event-group-event', 1, '${event.time} ASC');
        }
        if ($signupsAllowedOnly) {
            return array_filter(
                $this->allEvents,
                function ($e) {
                    return $e->signupsAllowed();
                }
            );
        } else {
            return $this->allEvents;
        }
    }

    public function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['eventgroup_form_preset'] = [
            'label' => 'Use preset form type',
            'field' => 'eventgroup.form.preset',
            'class' => 'fieldvalue',
            'weight' => 210,
            'required' => false,
            'extraConstructArgs' => [
                ['event-group'],
                ['eventgroup.form.preset'],
                $this->cms()->helper('permissions')->check('form/newpreset', 'events'),
            ],
        ];
        $map['eventgroup_launchtime'] = [
            'label' => 'Launch time',
            'field' => 'eventgroup.launchtime',
            'class' => 'datetime',
            'weight' => 300,
            'required' => false,
            'tips' => [
                'If specified, this field defines the time at which this event will become the "current" event and take over as the home page.',
            ],
        ];
        return $map;
    }
}
