<?php

namespace Digraph\Modules\ous_event_management\Chunks\jSignature;

use Digraph\Modules\ous_event_management\Chunks\AbstractChunk;

class Waiver extends AbstractChunk
{
    protected $label = 'Waiver agreement';
    const WEIGHT = 1000;

    public function instructions(): ?string
    {
        return
            '<div class="digraph-card incidental">' .
            '<p>Please be advised that, in connection with the production and publication of The University of New Mexico institutional promotional material, filming/taping and photographic recording will take place at live events such as, but not limited to, commencement ceremonies. Similarly, if any such event is conducted virtually, online, or by otherwise utilizing a digital platform, all video, photographic, or audio recordings can be recorded and used as described herein.</p>' .
            '<p>The information provided in this form may be used in connection with any recorded video, audio, or photographic documentation taken during the event or in promotion for the event or University. People in public areas near the event may appear in pictures and/or videos. Please be aware that by signing up for the event or entering the area, you grant The University of New Mexico and its designees the irrevocable right to use your voice, image, name and likeness, without compensation, in all manners in connection with the picture, including composite or modified representations, for advertising, trade or any other lawful purposes, and you release The University of New Mexico and its designees from all liability in connection therein.</p>' .
            '</div>';
    }

    public function body_edit()
    {
        $this->jSignature();
        parent::body_edit();
    }

    public function body_complete()
    {
        if ($this->signup[$this->name . '.signature'] != 'image/jsignature;base30,' && substr($this->signup[$this->name . '.signature'], 0, 24) == 'image/jsignature;base30,') {
            $svg = new jSignature_Tools_SVG();
            $b30 = new jSignature_Tools_Base30();
            $signature = $this->signup[$this->name . '.signature'];
            $signature = substr($signature, 24);
            $signature = $b30->Base64ToNative($signature);
            echo '<div class="jsignature-svg-signature">';
            echo $svg->NativeToSVG($signature);
            echo '</div>';
        } else {
            if (!preg_match('/^OTHER: /', $this->signup[$this->name . '.signature'])) {
                echo '<div class="jsignature-text-signature">' . htmlspecialchars($this->signup[$this->name . '.signature']) . '</div>';
            } else {
                echo $this->instructions();
            }
        }
        echo "<div class='incidental'>";
        echo "first signed " . $this->signup[$this->name . '.chunk.created.time'];
        $user = $this->signup->cms()->helper('users')->user($this->signup[$this->name . '.chunk.created.user']);
        echo " by " . ($user ? $user->name() : 'anonymous');
        if ($this->signup[$this->name . '.chunk.created.user'] != $this->signup[$this->name . '.chunk.modified.user']) {
            echo "<br><br>last signed " . $this->signup[$this->name . '.chunk.modified.time'];
            $user = $this->signup->cms()->helper('users')->user($this->signup[$this->name . '.chunk.created.user']);
            echo " by " . ($user ? $user->name() : 'anonymous');
        }
        echo "</div>";
    }

    public function body_incomplete()
    {
        echo "You must review and agree to the waiver";
    }

    protected function jSignature()
    {
        $baseURL = $this->signup->cms()->helper("urls")->parse('/');
        echo "<!--[if lt IE 9]><script type='text/javascript' src='$baseURL/jsignature/flashcanvas.js'></script><![endif]-->";
        echo "<script src='$baseURL/jsignature/jSignature.min.js'></script>";
        echo "<script src='$baseURL/jsignature/integration.js'></script>";
    }

    protected function form_map(): array
    {
        $user = $this->signup->cms()->helper('users')->user();
        $netid = $user['netid'] ?? $user->identifier();
        if ($this->signup['signup.for'] != $netid && $this->signup['signup.netid'] != $netid) {
            return [
                'checkbox' => [
                    'label' => 'Filled out by ' . $netid,
                    'field' => $this->name . '.checkbox',
                    'class' => 'checkbox',
                    'weight' => 500,
                    'required' => true,
                ],
                'signature' => [
                    'label' => 'Signature',
                    'field' => $this->name . '.signature',
                    'class' => 'text',
                    'weight' => 510,
                    'required' => true,
                    'call' => [
                        'addClass' => ['hidden'],
                        'value' => ['OTHER: ' . $netid]
                    ],
                ],
            ];
        } else {
            // regular people signature field
            return [
                'checkbox' => [
                    'label' => 'I have read and agree to the above',
                    'field' => $this->name . '.checkbox',
                    'class' => 'checkbox',
                    'weight' => 500,
                    'required' => true,
                ],
                'signature' => [
                    'label' => 'Signature',
                    'field' => $this->name . '.signature',
                    'class' => 'text',
                    'weight' => 510,
                    'required' => true,
                    'call' => [
                        'addClass' => ['jSignature'],
                    ],
                ],
            ];
        }
    }
}
