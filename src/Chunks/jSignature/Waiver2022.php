<?php

namespace Digraph\Modules\ous_event_management\Chunks\jSignature;

use Digraph\Modules\ous_event_management\Chunks\AbstractChunk;

class Waiver2022 extends Waiver
{
    public function instructions(): ?string
    {
        return
            '<div class="digraph-card incidental">' .
            '<p>Please be advised that, in connection with the production and publication of The University of New Mexico institutional promotional material, filming/taping and photographic recording will take place at live events such as, but not limited to, commencement ceremonies. Similarly, if any such event is conducted virtually, online, or by otherwise utilizing a digital platform, all video, photographic, or audio recordings can be recorded and used as described herein.</p>' .
            '<p>People in public areas near the event may appear in pictures and/or videos. Please be aware that by signing up for the event or entering the area, you grant The University of New Mexico and its designees the irrevocable right to use your voice, image, name and likeness, without compensation, in all manners in connection with the picture, including composite or modified representations, for advertising, trade or any other lawful purposes, and you release The University of New Mexico and its designees from all liability in connection therein.</p>' .
            '</div>';
    }
}
