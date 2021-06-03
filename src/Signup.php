<?php

namespace Digraph\Modules\ous_event_management;

use Digraph\DSO\Noun;
use Digraph\Modules\ous_event_management\Chunks\Contact\AbstractContactInfo;
use Digraph\Modules\ous_event_management\Chunks\Pages\AbstractPersonalizedPage;

class Signup extends Noun
{
    const FILESTORE = true;
    const SLUG_ID_LENGTH = 8;
    const HOOK_TRIGGER_PARENTS = false;
    const HOOK_TRIGGER_CHILDREN = false;

    protected $signupWindow;
    protected $allEvents;
    protected $chunks;

    public function attended(string $eventID, bool $set = null): ?bool
    {
        /** @var \Digraph\Graph\EdgeHelper */
        $e = $this->cms()->helper('edges');
        if ($set !== null) {
            $e->create(
                $eventID,
                $this['dso.id'],
                $set ? 'event-signup-attended-true' : 'event-signup-attended-false',
                -1
            );
        }
        if ($e->get($eventID, $this['dso.id'], 'event-signup-attended-true')) {
            return true;
        } elseif ($e->get($eventID, $this['dso.id'], 'event-signup-attended-false')) {
            return false;
        } else {
            return null;
        }
    }

    public function parent()
    {
        return $this->signupWindow() ?? parent::parent();
    }

    public function helper(): EventHelper
    {
        return $this->cms()->helper('events');
    }

    public function notificationEmails(): array
    {
        $to = [];
        if ($this->contactInfo()) {
            $to[] = $this->contactinfo()->email();
        }
        if ($user = $this->firstUserListUser()) {
            $to[] = $user['email'];
        }
        $for = $this['signup.for'];
        if (strpos($for, '@') === false) {
            $to[] = $for . '@unm.edu';
        } else {
            $to[] = $for;
        }
        $to = array_filter(array_unique($to));
        return $to;
    }

    public function hook_update()
    {
        parent::hook_update();
        foreach ($this->chunks() as $chunk) {
            $chunk->hook_update();
        }
        // set signup_windowtype
        if ($this->signupWindow()) {
            $this['signup_windowtype'] = $this->signupWindow()['signup_windowtype'];
            $this['signup_grouping'] = $this->signupWindow()['signup_grouping'];
        }
        // detect changes to completion status
        $complete = $this->complete();
        if ($complete && $this['complete.state'] != 'complete') {
            $this['complete.state'] = 'complete';
            $this['complete.time'] = time();
            // send an email when signup completion state changes to complete
            if (count($this->signupWindow()->allEvents()) < 2) {
                $this->helper()->sendMailTemplate($this, 'signup_standalone_complete');
            } else {
                $this->helper()->sendMailTemplate($this, 'signup_complete');
            }
        } elseif (!$complete) {
            // this signup is incomplete
            if ($this['complete.state'] == 'complete') {
                // we are toggling from complete to incomplete
                $this['complete.state'] = 'incomplete';
                $this['complete.time'] = time();
                // send an email when signup completion state changes from complete to incomplete
                if (count($this->signupWindow()->allEvents()) < 2) {
                    $this->helper()->sendMailTemplate($this, 'signup_standalone_incomplete');
                } else {
                    $this->helper()->sendMailTemplate($this, 'signup_incomplete');
                }
            } else {
                // we are toggling from another state to incomplete, which means
                // this signup was just created, at the moment this doesn't send
                // any emails
            }
        }
        // set digraph.name so that signups can be searched in Noun fields
        $this['digraph.name'] = $this->name();
    }

    public function personalizedPage(): ?AbstractPersonalizedPage
    {
        foreach ($this->chunks() as $chunk) {
            if ($chunk instanceof AbstractPersonalizedPage) {
                return $chunk;
            }
        }
        return null;
    }

    public function contactInfo(): ?AbstractContactInfo
    {
        foreach ($this->chunks() as $chunk) {
            if ($chunk instanceof AbstractContactInfo) {
                return $chunk;
            }
        }
        return null;
    }

    public function firstUserListUser()
    {
        if (!$this->signupWindow()) {
            return null;
        }
        return $this->signupWindow()->firstUserListUser($this['signup.for']);
    }

    public function allUserListUsers()
    {
        if (!$this->signupWindow()) {
            return null;
        }
        return $this->signupWindow()->allUserListUsers($this['signup.for']);
    }

    public function allowUpdate(): bool
    {
        // return true if user is allowed to edit noun by Digraph
        if ($this->isEditable()) {
            return true;
        }
        // signup window is open
        if ($this->signupWindow()->isOpen() || $this->cms()->helper('permissions')->check('form/ignoredeadlines', 'events')) {
            // allow if signup belongs to user
            if ($this->isMine()) {
                return true;
            }
        }
        // return false by default
        return false;
    }

    public function isMine(): bool
    {
        // allow if user is owner of signup
        if ($this['signup.owner'] == $this->cms()->helper('users')->id()) {
            return true;
        }
        // allow if user is subject of signup
        if ($this['signup.for'] == $this->cms()->helper('users')->userIdentifier()) {
            return true;
        }
        // return false by default
        return false;
    }

    public function allowViewing(): bool
    {
        // allow if signup belongs to user
        if ($this->isMine()) {
            return true;
        }
        // allow if updating is allowed
        if ($this->allowUpdate()) {
            return true;
        }
        // allow if permissions to view is set
        if ($this->cms()->helper('permissions')->check('signups/viewall', 'events')) {
            return true;
        }
        // return false by default
        return false;
    }

    public function chunks(): array
    {
        if ($this->chunks === null) {
            $this->chunks = [];
            // get chunks from signup window
            if ($this->eventGroup()) {
                $this->chunks = array_merge($this->chunks, $this->eventGroup()->chunks());
            }
            // get chunks from signup window
            if ($this->signupWindow()) {
                $this->chunks = array_merge($this->chunks, $this->signupWindow()->chunks());
            }
            // get chunks from this signup's events
            foreach ($this->allEvents() as $event) {
                $this->chunks = array_merge($this->chunks, $event->chunks());
            }
            // get own chunks
            $this->chunks = array_merge($this->chunks, $this->myChunks());
            // convert chunks here into actual chunk objects before returning
            array_walk(
                $this->chunks,
                function (&$e, $k) {
                    if ($e) {
                        $e = new $e($k, $this);
                    } else {
                        $e = false;
                    }
                }
            );
            $this->chunks = array_filter($this->chunks);
            // sort converted chunks by weight
            uasort($this->chunks, function ($a, $b) {
                return $a::WEIGHT - $b::WEIGHT;
            });
        }
        return $this->chunks;
    }

    public function setEvents(array $newEvents)
    {
        $newEvents = array_filter(array_map(function ($e) {
            if ($e instanceof Event) {
                return $e['dso.id'];
            } elseif (is_string($e)) {
                return $e;
            } else {
                return false;
            }
        }, $newEvents));
        $oldEvents = array_map(function ($e) {
            return $e['dso.id'];
        }, $this->allEvents());
        $added = array_diff($newEvents, $oldEvents);
        $deleted = array_diff($oldEvents, $newEvents);
        $edges = $this->cms()->helper('edges');
        foreach ($added as $e) {
            $edges->create($e, $this['dso.id'], 'event-event-signup');
        }
        foreach ($deleted as $e) {
            $edges->delete($e, $this['dso.id']);
        }
    }

    public function primaryEvents(): array
    {
        return array_filter(
            $this->allEvents(),
            function ($e) {
                return $e::PRIMARY_EVENT;
            }
        );
    }

    public function secondaryEvents(): array
    {
        return array_filter(
            $this->allEvents(),
            function ($e) {
                return !$e::PRIMARY_EVENT;
            }
        );
    }

    public function allEvents(): array
    {
        if ($this->allEvents === null) {
            $this->allEvents = $this->cms()->helper('graph')->parents($this['dso.id'], 'event-event-signup');
        }
        return $this->allEvents;
    }

    protected function myChunks(): array
    {
        return [];
    }

    public function signupWindow(): ?SignupWindow
    {
        if (!$this->signupWindow) {
            $ps = $this->cms()->helper('graph')->parents($this['dso.id'], 'event-signupwindow-signup');
            $this->signupWindow = array_shift($ps);
        }
        return $this->signupWindow;
    }

    public function eventGroup(): ?EventGroup
    {
        if ($this->signupWindow()) {
            return $this->signupWindow()->eventGroup();
        } else {
            return null;
        }
    }

    public function complete(): bool
    {
        foreach ($this->chunks() as $chunk) {
            if (!$chunk->complete()) {
                return false;
            }
        }
        if ($this->signupWindow() && $this->signupWindow()->allEvents()) {
            if (!$this->allEvents()) {
                return false;
            }
        }
        return true;
    }

    public function name($verb = null)
    {
        if ($this->contactInfo() && $this->contactInfo()->name()) {
            return $this->contactInfo()->name();
        }
        return $this['signup.for'];
    }

    public function title($verb = null)
    {
        $this->name($verb);
    }

    public function parentEdgeType(Noun $parent): ?string
    {
        if ($parent instanceof SignupWindow) {
            return 'event-signupwindow-signup';
        }
        if ($parent instanceof Event) {
            return 'event-event-signup';
        }
        return null;
    }

    public function formMap(string $action): array
    {
        return [
            'digraph_name' => false,
            'digraph_title' => false,
            'digraph_body' => false,
        ];
    }
}
