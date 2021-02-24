<?php
namespace Digraph\Modules\ous_event_management\Chunks\Regalia;

use Digraph\Forms\Fields\AbstractAutocomplete;

class DegreeFieldField extends AbstractAutocomplete
{
    const SOURCE = 'jostensdegree';

    protected function validateValue(string $value): bool
    {
        return !!$this->cms->helper('jostens')->locateDegree($value);
    }
}
