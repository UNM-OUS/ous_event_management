<?php
namespace Digraph\Modules\ous_event_management\Chunks\Old;

use Digraph\Modules\ous_event_management\Chunks\AbstractChunk;
use Formward\Fields\Hidden;

class WaiverOnline extends AbstractChunk
{
    protected $label = 'Waiver agreement';
    const WEIGHT = 1000;

    public function instructions(): ?string
    {
        return
            '<div class="digraph-card incidental">' .
            '<p>I, the undersigned student, understand that the University’s online graduation ceremony is available to the general public.  I further understand that by submitting pictures and/or videos (“Images”) for the University’s online graduation ceremony, I am hereby granting the University of New Mexico an irrevocable right to use my voice, image, name and likeness without compensation, in all manners in connection with the Images, including composite or modified representations, for advertising, promotional materials, the graduation ceremony, trade or any other lawful purposes, and you release The University of New Mexico, the Board of Regents, its employees and its designees from all liability in connection therein.</p>' .
            '</div>'
        ;
    }

    public function body_edit()
    {
        $this->jSignature();
        parent::body_edit();
    }

    public function body_complete()
    {
        echo "Waiver agreement complete";
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
        return [
            'checkbox' => [
                'label' => 'I have read and agree to the above',
                'field' => 'agreements.checkbox',
                'class' => 'checkbox',
                'weight' => 500,
                'required' => true,
            ],
            'signature' => [
                'label' => 'Signature',
                'field' => 'agreements.signature',
                'class' => 'text',
                'weight' => 510,
                'required' => true,
                'call' => [
                    'addClass' => ['jSignature'],
                ],
            ],
            'time' => [
                'label' => 'Time recorded',
                'field' => 'agreements.time',
                'class' => Hidden::class,
                'weight' => 500,
                'required' => true,
                'call' => [
                    'value' => [date("F j, Y, g:i a")],
                ],
            ],
            'class' => [
                'label' => 'Agreement class',
                'field' => 'agreements.class',
                'class' => Hidden::class,
                'weight' => 500,
                'required' => true,
                'call' => [
                    'value' => [static::class],
                ],
            ],
            'hash' => [
                'label' => 'Class hash',
                'field' => 'agreements.hash',
                'class' => Hidden::class,
                'weight' => 500,
                'required' => true,
                'call' => [
                    'value' => [md5_file(__FILE__)],
                ],
            ],
        ];
    }

}
