<?php
namespace Digraph\Modules\ous_event_management\Chunks\Contact;

class FacultyContactInformation extends AbstractContactInfo
{
    protected $label = 'Contact information';
    const WEIGHT = 100;

    protected function form_map(): array
    {
        $map = parent::form_map();
        $map['phone']['required'] = true;
        return $map;
    }
}
