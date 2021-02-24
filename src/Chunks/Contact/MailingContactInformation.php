<?php
namespace Digraph\Modules\ous_event_management\Chunks\Contact;

use Digraph\Modules\ous_event_management\Chunks\AbstractChunk;

class MailingContactInformation extends AbstractContactInfo
{
    protected $label = 'Contact information';
    const WEIGHT = 100;

    public function complete(): bool
    {
        return AbstractChunk::complete();
    }

    protected function form_map(): array
    {
        $map = parent::form_map();
        $map['mailingaddress'] = [
            'label' => 'Mailing Address',
            'field' => 'contact.mailingaddress',
            'class' => MailingAddressField::class,
            'weight' => 300,
            'required' => true,
        ];
        return $map;
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
        echo "<dt>Mailing Address</dt><dd>" . $this->addressHTML() . "</dd>";
        echo "</dl>";
    }

    public function address(): ?array
    {
        return array_map('\htmlentities', $this->signup['contact.mailingaddress']);
    }

    public function addressHTML(): string
    {
        if ($address = $this->address()) {
            $out = $this->name();
            $out .= '<br>' . $address['address'];
            if ($address['apartment']) {
                $out .= '<br>' . $address['apartment'];
            }
            $out .= '<br>' . $address['city'] . ', ' . $address['state'] . ' ' . $address['zip'];
            return "<address>$out</address>";
        }
        return '<address><em>[no address]</em></address>';
    }
}
