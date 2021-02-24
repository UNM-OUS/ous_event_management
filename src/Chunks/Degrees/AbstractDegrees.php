<?php
namespace Digraph\Modules\ous_event_management\Chunks\Degrees;

use Digraph\Forms\Form;
use Digraph\Modules\ous_event_management\Chunks\AbstractChunk;

abstract class AbstractDegrees extends AbstractChunk
{
    protected $label = 'Degree information';
    const WEIGHT = 100;

    protected function buttonText_cancelEdit()
    {
        return "cancel updating";
    }

    protected function buttonText_edit()
    {
        return "check for updates";
    }

    protected function buttonText_editIncomplete()
    {
        return "check for updates";
    }

    public function pullDegrees(): array
    {
        $degrees = $this->signup->allUserListUsers();
        if (!$this->signup['degrees']) {
            $this->signup['degrees'] = $degrees;
            $this->signup->update();
        }
        return $degrees;
    }

    public function form(): Form
    {
        $form = parent::form();
        $form->submitButton()->label('Confirm and save degrees');
        return $form;
    }

    public function degrees(): array
    {
        return $this->signup['degrees'] ?? $this->pullDegrees();
    }

    public function instructions(): ?string
    {
        $out = '';
        $degrees = $this->pullDegrees();
        if ($this->signup['degrees'] == $degrees) {
            $out .= '<div class="notification notification-confirmation">Your degree records have not changed since the last time your signup was updated.</div>';
        }else {
            $out .= '<div class="notification notification-warning">Your degree records have changed since the last time your signup was updated. Click "Confirm and save degrees" to save the updated degrees into your signup.</div>';
            if ($this->signup->personalizedPage()) {
                $out .= '<div class="notification notification-notice">Updating degrees will <em>not</em> require your personalized page to be re-moderated.</div>';
            }
        }
        $out .= $this->degreesHTML($degrees);
        $out .= '<div class="notification notification-notice">Degree records are pulled periodically from Banner. If what you see here isn\'t what you expected, please check first with your academic advisor.</div>';
        return $out;
    }

    public function degreesHTML(array $degrees)
    {
        $out = '';
        if (!$degrees) {
            $out .= '<div class="notification notification-error">No degrees found.</div>';
        }
        foreach ($degrees as $d) {
            $out .= '<div class="digraph-card">';
            if (@$d['name']) {
                $out .= '<div><strong>' . $d['name'] . '</strong></div>';
            }
            $out .= '<div><strong>' . @$d['degree'] . '</strong></div>';
            if (@$d['major']) {
                $out .= '<div><em>Major:</em> ' . $d['major'];
                if (@$d['second major']) {
                    $out .= ', ' . $d['second major'];
                }
                $out .= "</div>";
            }
            if (@$d['minor']) {
                $out .= '<div><em>Minor:</em> ' . $d['minor'];
                if (@$d['second minor']) {
                    $out .= ', ' . $d['second minor'];
                }
                $out .= "</div>";
            }
            if (@$d['honors']) {
                $out .= '<div><em>University Honors:</em> ' . $d['honors'] . '</div>';
            }
            if (@$d['academic period']) {
                $out .= '<div><em>' . $d['graduation status'] . ' ' . $d['academic period'] . '</em></div>';
            }
            $out .= '</div>';
        }
        return $out;
    }

    public function body_complete()
    {
        echo $this->degreesHTML($this->degrees());
    }

    public function body_incomplete()
    {
        echo $this->degreesHTML($this->degrees());
    }

    protected function onFormHandled()
    {
        unset($this->signup['degrees']);
        $this->signup['degrees'] = $this->degrees();
    }

    public function complete(): bool
    {
        return !!$this->degrees();
    }

    protected function form_map(): array
    {
        return [];
    }
}
