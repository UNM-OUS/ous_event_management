<?php
namespace Digraph\Modules\ous_event_management\Chunks\Regalia;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Container;
use Formward\Fields\Number;
use Formward\Fields\Select;

class RegaliaSizeField extends Container
{
    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms=null)
    {
        parent::__construct($label, $name, $parent);
        // gender
        $this['gender'] = new Select('Gender');
        $this['gender']->options([
            'Male' => 'Male',
            'Female' => 'Female',
            'Non-binary' => 'Non-binary',
            'Other' => 'Other',
            'Prefer not to share' => 'Prefer not to share',
        ]);
        // hat
        $this['hat'] = new Select('Hat size');
        $this['hat']->options([
            'XS' => 'XS: 19-1/4" — 20-1/8"',
            'S' => 'S: 20-1/4" — 21-1/8"',
            'M' => 'M: 21-1/4" — 22-7/8"',
            'L' => 'L: 23" — 24-1/8"',
            'XL' => 'XL: 24-1/4" — 26"',
        ]);
        $this['hat']->addTip('Measure your head circumference 1" above your ears, and select hat size accordingly.');
        // body measurements
        $this['height'] = new HeightField('Height');
        $this['weight'] = new Number('Weight in pounds');
    }
}
