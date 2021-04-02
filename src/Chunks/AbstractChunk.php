<?php
namespace Digraph\Modules\ous_event_management\Chunks;

use Digraph\Forms\Form;
use Digraph\Modules\ous_event_management\Signup;

/**
 * Chunks are used to organize the forms that make up a signup into
 * discrete chunks that can be requested by signup classes, signup
 * windows, events, and/or event groups.
 */
abstract class AbstractChunk implements ChunkInterface
{
    protected $signup;
    protected $name;
    protected $label = 'Untitled section';
    protected $form;

    abstract protected function form_map(): array;

    public function hook_update()
    {
        //does nothing, called before signup is updated
    }

    public function instructions(): ?string
    {
        return null;
    }

    protected function onFormHandled()
    {
        // does nothing, but can be used to hook into form submission, before saving
    }

    public function body_edit()
    {
        $form = $this->form();
        echo $form;
    }

    public function body_complete()
    {
    }

    public function body_incomplete()
    {
    }

    protected function buildForm($forValidation = false): Form
    {
        $map = $this->form_map();
        uasort($map, function ($a, $b) {
            return $a['weight'] - $b['weight'];
        });
        return $this->signup->cms()->helper('forms')->mapForm(
            $this->signup,
            $map,
            $this->name . ($forValidation ? '_validation' : '')
        );
    }

    public function form(): Form
    {
        if ($this->form === null) {
            $this->form = $this->buildForm();
            $this->form->action('');
            if ($this->form->handle()) {
                // set up user/time metadata object
                $modMeta = [
                    'time' => date("F j, Y, g:i a"),
                    'user' => $this->signup->cms()->helper('users')->id() ?? 'guest',
                ];
                // set chunk creation metadata
                if (!$this->signup[$this->name . '.chunk.created']) {
                    $this->signup[$this->name . '.chunk.created'] = $modMeta;
                }
                // set chunk modified metadata
                $this->signup[$this->name . '.chunk.modified'] = $modMeta;
                // call hooks
                $this->onFormHandled();
                $this->signup->update();
            }
            $this->form->submitButton()->label('Save section');
        }
        return $this->form;
    }

    public function __construct(string $name, Signup $signup)
    {
        $this->name = $name;
        $this->signup = $signup;
    }

    public function complete(): bool
    {
        return $this->buildForm(true)->validate();
    }

    protected function buttonText_cancelEdit()
    {
        return "cancel editing";
    }

    protected function buttonText_edit()
    {
        return "edit section";
    }

    protected function buttonText_editIncomplete()
    {
        return "complete section";
    }

    public function body(?bool $edit = null, ?bool $iframe = null): string
    {
        //uses output buffering for convenience
        ob_start();
        //determine if we're editing
        $edit = $edit && $this->signup->allowUpdate();
        $mode = ($this->complete() ? 'complete' : 'incomplete');
        if ($edit) {
            $mode .= ' editing';
        }
        //open chunk and add label
        echo "<div class='signup-chunk $mode'>";
        echo "<div class='signup-chunk-label'>" . $this->label . "</div>";
        //main body
        if ($edit) {
            //instructions
            if ($this->instructions()) {
                echo "<div class='digraph-block instructions'>" . $this->instructions() . "</div>";
            }
            //display editing form if editing is allowed and either incomplete or edit requested
            echo "<div class='chunk-body chunk-body-edit'>";
            $this->body_edit();
        } elseif ($this->complete()) {
            //display complete content if completed
            echo "<div class='chunk-body chunk-body-complete'>";
            $this->body_complete();
        } else {
            //display incomplete content if incomplete and not editable
            echo "<div class='chunk-body chunk-body-incomplete'>";
            $this->body_incomplete();
        }
        echo "</div>";
        //display edit/cancel edit links
        if ($this->signup->allowUpdate()) {
            if ($edit) {
                $url = $this->signup->url('chunk', [
                    'chunk' => $this->name,
                    'iframe' => $iframe,
                ], true);
                echo "<a class='mode-switch' href='$url'>" . $this->buttonText_cancelEdit() . "</a>";
            } else {
                $url = $this->signup->url('chunk', [
                    'chunk' => $this->name,
                    'edit' => true,
                    'iframe' => $iframe,
                ], true);
                if ($this->complete()) {
                    echo "<a class='mode-switch' href='$url'>" . $this->buttonText_edit() . "</a>";
                } else {
                    echo "<a class='mode-switch' href='$url'>" . $this->buttonText_editIncomplete() . "</a>";
                }
            }
        }
        echo "</div>";
        //output
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }
}
