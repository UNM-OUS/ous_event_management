<?php
namespace Digraph\Modules\ous_event_management\Chunks\Regalia;

use Digraph\Modules\ous_event_management\Chunks\AbstractChunk;

class RegaliaOrderChunk extends AbstractChunk
{
    protected $label = 'Regalia order';
    const WEIGHT = 200;

    protected function buttonText_cancelEdit()
    {
        return "cancel editing";
    }

    protected function buttonText_edit()
    {
        return "edit regalia order";
    }

    protected function buttonText_editIncomplete()
    {
        return "edit regalia order";
    }

    public function body_complete()
    {
        // opted out of regalia
        if ($this->signup['regalia.order.optout']) {
            $this->signup->cms()->helper('notifications')
                ->printConfirmation("Opted out: Owns or does not need regalia");
            return;
        }
        // tell person where to pick up their regalia?
        // TODO: figure out how this should work
        // regalia order specified
        echo "<dl>";
        if ($this->signup['regalia.order.parts']) {
            echo "<dt>Pieces needed</dt>";
            echo "<dd>" . implode(', ', $this->signup['regalia.order.parts']) . "</dd>";
        }
        if ($this->signup['regalia.degree.institution']) {
            echo "<dt>Alma mater</dt>";
            echo "<dd>" . $this->signup['regalia.degree.institution'] . "</dd>";
        }
        if ($this->signup['regalia.degree.level']) {
            echo "<dt>Degree/Level</dt>";
            echo "<dd>" . preg_replace('/:.*$/', '', $this->signup['regalia.degree.level']) . "</dd>";
        }
        if ($this->signup['regalia.degree.field'] && $this->signup['regalia.degree.field'] != 'PHD') {
            echo "<dt>Degree field of study</dt>";
            echo "<dd>" . $this->signup['regalia.degree.field'] . "</dd>";
        }
        if ($this->signup['regalia.size']) {
            $size = $this->signup['regalia.size'];
            $size['height'] = $size['height']['ft'] . '\' ' . $size['height']['in'] . '"';
            foreach ($size as $k => $v) {
                $size[$k] = "$k: $v";
            }
            echo "<dt>Regalia sizing</dt>";
            echo "<dd>" . implode('<br>', $size) . "</dd>";
        }
        echo "</dl>";
    }

    public function body_incomplete()
    {
        $this->printRequirementNotification();
        $this->body_complete();
    }

    protected function printRequirementNotification()
    {
        if (count($this->signup->allEvents()) > 1) {
            if ($this->signup->regaliaRequirement() == 'required') {
                $this->signup->cms()->helper('notifications')->printNotice(
                    'One or more selected event has indicated that regalia is required. For more information about each event, see the links at the top of the signup form.'
                );
            } elseif ($this->signup->regaliaRequirement() == 'optional') {
                $this->signup->cms()->helper('notifications')->printNotice(
                    'One or more selected event has indicated that regalia is optional, but not required. For more information about each event, see the links at the top of the page.'
                );
            }
        } else {
            if ($this->signup->regaliaRequirement() == 'required') {
                $this->signup->cms()->helper('notifications')->printNotice(
                    'This event has indicated that regalia is required.'
                );
            } elseif ($this->signup->regaliaRequirement() == 'optional') {
                $this->signup->cms()->helper('notifications')->printNotice(
                    'This event has indicated that regalia is optional, but not required.'
                );
            }
        }
    }

    protected function form_map(): array
    {
        return [
            'regalia' => [
                'label' => '',
                'class' => RegaliaComboField::class,
                'field' => 'regalia',
            ],
        ];
    }
}
