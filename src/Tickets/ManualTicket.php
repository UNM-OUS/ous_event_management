<?php

namespace Digraph\Modules\ous_event_management\Tickets;

use Digraph\DSO\Noun;
use Digraph\Modules\ous_digraph_module\Fields\NetID;
use Formward\Fields\Email;

class ManualTicket extends Noun
{
    public function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['digraph_title'] = false;
        $map['digraph_body'] = false;
        $map['digraph_name']['tips'] = [
            'Will be displayed under QR code as if it were the signup name'
        ];
        $map['netid'] = [
            'label' => 'NetID',
            'field' => 'signup.for',
            'class' => NetID::class,
            'tips' => [
                'Optionally enter a NetID here to limit access to the ticket'
            ],
            'weight' => 500
        ];
        $map['email'] = [
            'label' => 'Email',
            'field' => 'contact.email',
            'class' => Email::class,
            'tips' => [
                'Optionally enter an email address to use for sending ticket links'
            ],
            'weight' => 500
        ];
        return $map;
    }
}
