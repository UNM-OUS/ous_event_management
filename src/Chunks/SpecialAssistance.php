<?php
namespace Digraph\Modules\ous_event_management\Chunks;

use Formward\Fields\CheckboxList;

class SpecialAssistance extends AbstractChunk
{
    protected $label = "Special assistance";

    public function hook_update()
    {
        $value = $this->signup[$this->name];
        if (@$value['standard'] || @$value['other']) {
            $this->signup[$this->name.'.required'] = true;
        }else {
            $this->signup[$this->name.'.required'] = false;
        }
    }

    protected function form_map(): array
    {
        $map = [];
        $name = $this->name;
        $map['standard'] = [
            'label' => 'Special assistance required',
            'field' => "$name.standard",
            'class' => CheckboxList::class,
            'options' => [
                'Wheelchair access' => 'Wheelchair access',
                'Inability to negotiate stairs' => 'Inability to negotiate stairs',
                'Use of cane, walker, or crutches' => 'Use of cane, walker, or crutches',
                'Requires signed language interpreter' => 'Requires signed language interpreter',
            ],
            'weight' => 100,
        ];
        $map['other'] = [
            'label' => 'Other assistance needed',
            'field' => "$name.other",
            'class' => 'textarea',
            'weight' => 200,
        ];
        return $map;
    }

    public function complete(): bool
    {
        return true;
    }

    public function body_complete()
    {
        $value = $this->signup[$this->name];
        if (@$value['standard'] || @$value['other']) {
            if (@$value['standard']) {
                echo "<ul><li>";
                echo implode('</li><li>', $value['standard']);
                echo "</li></ul>";
            }
            if (@$value['other']) {
                $f = $this->signup->cms()->helper('filters');
                echo "<blockquote>";
                echo $f->filterPreset($value['other'], 'text-safe');
                echo "</blockquote>";
            }
        } else {
            echo "<p><em>No special assistance requested</em></p>";
        }
    }

}
