<?php
namespace Digraph\Modules\ous_event_management\Chunks\Regalia;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Container;

class RegaliaComboField extends Container
{
    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms = null)
    {
        parent::__construct($label, $name, $parent);
        $this->addClass('TransparentContainer');
        $this['order'] = new RegaliaOrderField('', null, null, $cms);
        $this['degree'] = new DegreeInfoField('Degree information', null, null, $cms);
        $this['size'] = new RegaliaSizeField('Regalia size information', null, null, $cms);
        $this['degree']->addTip('We need to know where you went to school and some details of your degree, because it will determine the style and trim colors of your regalia.');
    }

    public function validate(): bool
    {
        if (!$this['order']['optout']->value()) {
            $this['degree']->required(true, false);
            $this['size']->required(true, false);
        }
        return parent::validate();
    }
}
