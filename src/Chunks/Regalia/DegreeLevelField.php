<?php
namespace Digraph\Modules\ous_event_management\Chunks\Regalia;

use Formward\FieldInterface;
use Formward\Fields\Select;

class DegreeLevelField extends Select
{
    public function __construct(string $label, string $name=null, FieldInterface $parent=null)
    {
        parent::__construct($label, $name, $parent);
        $this->options([
            "DOCTOR: PHD" => "Doctoral - PHD",
            "DOCTOR: NURSING" => "Doctoral - DNP",
            "DOCTOR: PHYSICAL THERAPY" => "Doctoral - DPT",
            "DOCTOR: EDUCATION" => "Doctoral - EDD",
            "DOCTOR: JURIS DOCTOR" => "Doctoral - Juris Doctor",
            "DOCTOR: FINE ARTS" => "Doctoral - MFA",
            "DOCTOR: MEDICINE" => "Doctoral - MD",
            "DOCTOR: PHARMACY" => "Doctoral - PHARMD",
            "DOCTOR" => "Doctoral - Other terminal degrees",
            "MASTER" => "Master's",
            "BACHELOR" => "Bachelor's",
            "ASSOCIATE" => "Associate's",
            "OTHER" => "Other",
        ]);
    }
}
