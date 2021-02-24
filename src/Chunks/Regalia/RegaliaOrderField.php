<?php
namespace Digraph\Modules\ous_event_management\Chunks\Regalia;

use Formward\FieldInterface;
use Formward\Fields\Checkbox;
use Formward\Fields\CheckboxList;
use Formward\Fields\Container;

class RegaliaOrderField extends Container
{
    public function __construct(string $label, string $name = null, FieldInterface $parent = null)
    {
        parent::__construct($label, $name, $parent);
        // $this->addClass('TransparentContainer');
        // set up basic fields
        $this['optout'] = new Checkbox('I either do not need or do not wish to rent regalia');
        $this['optout']->addTip('<strong>Please note:</strong> If you do not own or rent regalia, it is your responsibility to verify that it is not required for the events you wish to participate in.');
        $this['optout']->addClass('regalia-optout-checkbox');
        $this['parts'] = new CheckboxList('Regalia pieces needed');
        $this['parts']->options([
            'hat' => 'Cap or tam',
            'hood' => 'Hood',
            'robe' => 'Robe',
        ]);
        $this['parts']->addTip('If you already own a partial set of regalia you can rent just the pieces you are missing.');
        $this['parts']->addTip('Whether you receive a mortarboard cap or a tam will be determined later, depending on the requirements of your selected events and your role in those events.');
        // validate that either opt-out or at least one piece is checked
        $this->addValidatorFunction(
            'pick',
            function($field) {
                $value = $field->value();
                if (!$value['optout'] && !$value['parts']) {
                    return 'You must either opt out or select at least one piece of regalia to rent.';
                }
                return true;
            }
        );
        // $this['department'] = new Noun('Billing department');
        // $this['department']->limitTypes(['convocation-org']);
        // $this['department']->addTip('If you attend the main Commencement ceremony the Office of the Secretary of will pay for your regalia rental.');
        // $this['department']->addTip('If you fail to sign in at Commencement your regalia rental will be billed to your department (generally this field should match one of the convocations you are planning to attend).');
    }
}
