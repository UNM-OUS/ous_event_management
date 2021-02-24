<?php
namespace Digraph\Modules\ous_event_management\Chunks\Regalia;

use Digraph\Forms\Fields\AbstractAutocomplete;

class AlmaMaterField extends AbstractAutocomplete
{
    const SOURCE = 'jostensalmamater';

    protected function validateValue(string $value): bool
    {
        return !!$this->cms->helper('jostens')->locateInstitution($value);
    }
}
