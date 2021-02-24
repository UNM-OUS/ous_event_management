<?php
namespace Digraph\Modules\ous_event_management\Chunks\Regalia;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Container;

class DegreeInfoField extends Container
{
    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms=null)
    {
        parent::__construct($label, $name, $parent);
        $this['institution'] = new AlmaMaterField('Alma mater', null, null, $cms);
        $this['level'] = new DegreeLevelField('Degree level');
        $this['field'] = new DegreeFieldField('Degree field', null, null, $cms);
    }
}
