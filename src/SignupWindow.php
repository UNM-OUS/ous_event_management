<?php

namespace Digraph\Modules\ous_event_management;

use Digraph\DSO\Noun;

class SignupWindow extends Noun
{
    const SLUG_ENABLED = true;
    const SLUG_ID_LENGTH = 6;

    protected $eventGroup;
    protected $userLists;
    protected $allEvents;

    public function signupGrouping(): array
    {
        return preg_split('/ *, */', $this['signup_grouping']);
    }

    public function chunks(): array
    {
        if ($p = $this['signupwindow.form.preset']) {
            if ($c = $this->cms()->config['events.forms.presets.' . $p]) {
                return $c;
            }
        }
        return [];
    }

    protected function myChunks(): array
    {
        return [];
    }

    /**
     * Return the event group's list of events, filtered according to this
     * window's rules. Currently filters out events with a different
     * value in signup_grouping.
     *
     * @return array
     */
    public function allEvents(): array
    {
        if (!$this->eventGroup()) {
            return [];
        }
        if ($this->allEvents === null) {
            $this->allEvents = array_filter(
                $this->eventGroup()->allEvents(true),
                function (Event $e) {
                    if ($e['signup_disabled'] || $e['cancelled']) {
                        return false;
                    }
                    return !!array_intersect($this->signupGrouping(), $e->signupGrouping());
                }
            );
        }
        return $this->allEvents;
    }

    /**
     * Return the primary, OUS-managed events for this window
     *
     * @return array
     */
    public function primaryEvents(): array
    {
        return array_filter(
            $this->allEvents(),
            function ($e) {
                return $e::PRIMARY_EVENT;
            }
        );
    }

    /**
     * Return the secondary events for this window
     *
     * @return array
     */
    public function secondaryEvents(): array
    {
        return array_filter(
            $this->allEvents(),
            function ($e) {
                return !$e::PRIMARY_EVENT;
            }
        );
    }

    public function eventGroup(): EventGroup
    {
        if ($this->eventGroup === null) {
            $ps = $this->cms()->helper('graph')->parents($this['dso.id'], 'event-group-signupwindow');
            $this->eventGroup = array_shift($ps);
        }
        return $this->eventGroup;
    }

    public function canSignupOthers(): bool
    {
        $p = $this->cms()->helper('permissions');
        if ($p->checkUrl($this->url('edit'))) {
            // user can edit window, so signups are unrestricted
            return true;
        } elseif ($p->check('form/signupothers', 'events')) {
            // user has events: form/signupothers permissions
            return true;
        } else {
            return false;
        }
    }

    public function isOpen(): bool
    {
        return time() > $this['signupwindow.time.start'] && time() < $this['signupwindow.time.end'];
    }

    public function signupAllowed(): bool
    {
        $p = $this->cms()->helper('permissions');
        if ($p->checkUrl($this->url('edit'))) {
            // user can edit window, so signups are unrestricted
            return true;
        } elseif ($this->isOpen() || $p->check('form/ignoredeadlines', 'events')) {
            // signup window is open or user has form/ignoredeadlines permission
            // return false if user isn't allowed to view signup URL
            if (!$p->checkUrl($this->url('signup'))) {
                return false;
            }
            // check if user is disallowed to sign up via UserLists
            // only runs if user isn't allowed to sign up others
            if ($this['signupwindow.limit_signups'] && !$this->canSignupOthers()) {
                // signups are limited, only return true if userlist user is found
                return !!$this->firstUserListUser();
            } else {
                // otherwise return true for open window
                return true;
            }
        } else {
            // return false for a closed window
            return false;
        }
    }

    public function allUserListUsers(string $query = null)
    {
        if (!$query) {
            $query = $this->cms()->helper('users')->userIdentifier();
        }
        // try to load from cache
        $cacheID = md5($this['dso.id'] . '/allUserListUsers/' . $query);
        $cache = $this->cms()->cache();
        if ($cache->hasItem($cacheID)) {
            return $cache->getItem($cacheID)->get();
        }
        // find results
        $results = [];
        foreach ($this->userLists() as $list) {
            foreach ($list->findAll($query) as $user) {
                $results[] = $user;
            }
        }
        // cache and return result
        $citem = $cache->getItem($cacheID);
        $citem->set($results);
        $citem->expiresAfter(3600);
        $cache->save($citem);
        return $results;
    }

    public function firstUserListUser(string $query = null)
    {
        if (!$query) {
            $query = $this->cms()->helper('users')->userIdentifier();
        }
        // try to load from cache
        $cacheID = md5($this['dso.id'] . '/firstUserListUser/' . $query);
        $cache = $this->cms()->cache();
        if ($cache->hasItem($cacheID)) {
            return $cache->getItem($cacheID)->get();
        }
        // find result
        $result = null;
        foreach ($this->userLists() as $list) {
            if ($user = $list->findFirst($query)) {
                $result = $user;
            }
        }
        // cache and return result
        $citem = $cache->getItem($cacheID);
        $citem->set($result);
        $citem->expiresAfter(3600);
        $cache->save($citem);
        return $result;
    }

    public function userLists(): array
    {
        if ($this->userLists === null) {
            $this->userLists = $this->cms()->helper('graph')->children($this['dso.id'], 'event-signupwindow-userlist');
        }
        return $this->userLists;
    }

    public function createSignup(string $for): Signup
    {
        $for = strtolower($for);
        $signup = $this->cms()->factory()->create([
            'dso' => [
                'type' => 'event-signup',
            ],
            'signup' => [
                'owner' => $this->cms()->helper('users')->id(),
                'for' => $for,
                'events' => [],
            ],
            'signup_windowtype' => $this['signup_windowtype'],
            'signup_grouping' => $this['signup_grouping'],
        ]);
        if (!$this->cms()->helper('edges')->create($this['dso.id'], $signup['dso.id'], 'event-signupwindow-signup')) {
            $signup->delete();
            throw new \Exception("Error occurred inserting edge to Signup");
        }
        return $signup;
    }

    public function signupsByOwner(string $owner = null): array
    {
        $owner = $owner ?? $this->cms()->helper('users')->id();
        $signupIDs = $this->cms()->helper('graph')->childIDs($this['dso.id'], 'event-signupwindow-signup');
        $search = $this->cms()->factory()->search();
        $search->where('${signup.owner} = :owner AND ${dso.id} IN ("' . implode('","', $signupIDs) . '")');
        $search->order('${dso.created.date} desc');
        return $search->execute(['owner' => $owner]);
    }

    public function findSignupFor(string $for = null): ?Signup
    {
        $for = $for ?? $this->cms()->helper('users')->userIdentifier();
        if ($signups = $this->findSignupsFor($for)) {
            return array_shift($signups);
        } else {
            return null;
        }
    }

    public function allSignups(): array
    {
        return $this->cms()->helper('graph')->children($this['dso.id'], 'event-signupwindow-signup');
    }

    public function findSignupsFor(string $for = null): array
    {
        $for = $for ?? $this->cms()->helper('users')->userIdentifier();
        $for = strtolower($for);
        $signupIDs = $this->cms()->helper('graph')->childIDs($this['dso.id'], 'event-signupwindow-signup');
        $search = $this->cms()->factory()->search();
        $search->where('${signup.for} = :for AND ${dso.id} IN ("' . implode('","', $signupIDs) . '")');
        $search->order('${dso.created.date} desc');
        return $search->execute(['for' => $for]);
    }

    public function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['digraph_title'] = false;
        $map['signupwindow_time'] = [
            'label' => 'Signup window start/end dates',
            'field' => 'signupwindow.time',
            'class' => 'datetime_range',
            'weight' => 200,
            'required' => true,
        ];
        $map['signupwindow_form_preset'] = [
            'label' => 'Use preset form type',
            'field' => 'signupwindow.form.preset',
            'class' => 'fieldvalue',
            'weight' => 210,
            'required' => false,
            'extraConstructArgs' => [
                ['event-signupwindow'],
                ['signupwindow.form.preset'],
                $this->cms()->helper('permissions')->check('form/newpreset', 'events'),
            ],
        ];
        $map['signupwindow_limit_signups'] = [
            'label' => 'Limit signups to supplied user lists',
            'field' => 'signupwindow.limit_signups',
            'class' => 'checkbox',
            'weight' => 220,
            'required' => false,
            'tips' => [
                'user-lists' => 'Add user-list objects as children of this signup window to define who is allowed to sign up',
            ],
        ];
        $map['signupwindow_unlisted'] = [
            'label' => 'Unlisted signup window',
            'field' => 'signupwindow.unlisted',
            'class' => 'checkbox',
            'weight' => 220,
            'required' => false,
            'tips' => [
                'unlisted-explanation' => 'Unlisted signup windows are not shown on public lists, and the only way to find them is to have a direct link',
            ],
        ];
        $map['signupwindow_emails'] = [
            'label' => 'Email messages enabled',
            'field' => 'signupwindow.email',
            'class' => 'checkbox',
            'weight' => 220,
            'required' => false,
            'default' => true,
            'tips' => [
                'Uncheck this box to disable sending of automatic emails about signups',
            ],
        ];
        $map['signup_windowtype'] = [
            'label' => 'Type/audience',
            'field' => 'signup_windowtype',
            'class' => 'fieldvalue',
            'weight' => 300,
            'required' => false,
            'tips' => [
                'Generally this is either "students" or "faculty" and is used to indicate the broad category of user that will use this form.',
            ],
            'extraConstructArgs' => [
                ['event-signupwindow'],
                ['signup_windowtype'],
                $this->cms()->helper('permissions')->check('form/newsignupwindowtype', 'events'),
            ],
        ];
        $map['signup_grouping'] = [
            'label' => 'Signup grouping',
            'field' => 'signup_grouping',
            'class' => 'fieldvalue',
            'weight' => 300,
            'required' => true,
            'default' => 'default',
            'tips' => [
                'Groupings can be used to limit signup windows to using only certain events. Signup windows will only allow signing up for events that share the same grouping value.',
            ],
            'extraConstructArgs' => [
                ['event', 'event-signupwindow'],
                ['signup_grouping'],
                $this->cms()->helper('permissions')->check('form/newgrouping', 'events'),
            ],
        ];
        return $map;
    }

    public function parentEdgeType(Noun $parent): ?string
    {
        if ($parent instanceof EventGroup) {
            return 'event-group-signupwindow';
        }
        return null;
    }
}
