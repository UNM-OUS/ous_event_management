<?php
namespace Digraph\Modules\ous_event_management\Chunks\Regalia;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Container;
use Formward\Fields\Number;

class HeightField extends Container
{
    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms = null)
    {
        parent::__construct($label, $name, $parent);
        // $this->addClass('TransparentContainer');
        $this->wrapContainerItems(false);
        $this['ft'] = new Number('Feet');
        $this['ft']->attr('max',8);
        $this['ft']->attr('min',2);
        $this['in'] = new Number('Inches');
        $this['in']->attr('max',11);
        $this['in']->attr('min',0);
    }
}