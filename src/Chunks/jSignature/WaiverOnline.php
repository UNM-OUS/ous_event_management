<?php
namespace Digraph\Modules\ous_event_management\Chunks\jSignature;

class WaiverOnline extends Waiver
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
}
