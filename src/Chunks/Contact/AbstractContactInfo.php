<?php
namespace Digraph\Modules\ous_event_management\Chunks\Contact;

use Digraph\Modules\ous_event_management\Chunks\AbstractChunk;
use Formward\Fields\Email;
use Formward\Fields\Phone;

abstract class AbstractContactInfo extends AbstractChunk
{
    protected $label = 'Contact information';
    const WEIGHT = 100;

    public function userListUser(): ?array
    {
        return $this->signup->firstUserListUser();
    }

    public function hook_update()
    {
        if (!$this->signup[$this->name]) {
            // try to find previous signups by this user
            $search = $this->signup->cms()->factory()->search();
            $search->where('${dso.type} = :type AND ${signup.for} = :for');
            $search->order('${dso.created.date} desc');
            $search->limit(1);
            if ($result = $search->execute(['type' => $this->signup['dso.type'], 'for' => $this->signup['signup.for']])) {
                $result = array_pop($result);
                $this->signup[$this->name] = $result[$this->name];
                return;
            }
            // try to find user in user lists
            if ($user = $this->userListUser()) {
                $this->signup[$this->name] = [
                    'firstname' => $user['first name'],
                    'lastname' => $user['last name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                ];
            }
        }
    }

    public function instructions(): ?string
    {
        return '';
    }

    public function name()
    {
        return implode(' ', array_filter([
            $this->firstName(),
            $this->lastName(),
        ]));
    }

    public function firstName()
    {
        return htmlentities($this->signup[$this->name . '.firstname']);
    }

    public function lastName()
    {
        return htmlentities($this->signup[$this->name . '.lastname']);
    }

    public function email()
    {
        return htmlentities($this->signup[$this->name . '.email']);
    }

    public function phone()
    {
        return htmlentities($this->signup[$this->name . '.phone']);
    }

    protected function form_map(): array
    {
        return [
            'firstname' => [
                'label' => 'First name',
                'field' => $this->name . '.firstname',
                'class' => 'text',
                'weight' => 100,
                'required' => true,
                'default' => $this->firstName(),
            ],
            'lastname' => [
                'label' => 'Last name',
                'field' => $this->name . '.lastname',
                'class' => 'text',
                'weight' => 110,
                'required' => true,
                'default' => $this->lastName(),
            ],
            'email' => [
                'label' => 'Email address',
                'field' => $this->name . '.email',
                'class' => Email::class,
                'weight' => 200,
                'required' => true,
                'default' => $this->email(),
            ],
            'phone' => [
                'label' => 'Phone number',
                'field' => $this->name . '.phone',
                'class' => Phone::class,
                'weight' => 300,
                'required' => false,
                'default' => $this->phone(),
            ],
        ];
    }

    public function body_complete()
    {
        echo "<dl>";
        if ($this->name()) {
            echo "<dt>Name</dt><dd>" . $this->name() . "</dd>";
        }
        if ($this->email()) {
            echo "<dt>Email</dt><dd>" . $this->email() . "</dd>";
        }
        if ($this->phone()) {
            echo "<dt>Phone</dt><dd>" . $this->phone() . "</dd>";
        }
        echo "</dl>";
    }

    public function body_incomplete()
    {
        $this->body_complete();
    }
}
