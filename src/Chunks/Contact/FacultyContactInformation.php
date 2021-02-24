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
        $map['phone']['tips'] = [
            'Please provide a number that will be effective for contacting you, especially if you are ordering regalia or will be hooding a doctoral student.',
        ];
        return $map;
    }
}
