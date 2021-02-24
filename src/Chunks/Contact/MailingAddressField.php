<?php
namespace Digraph\Modules\ous_event_management\Chunks\Contact;

use Formward\FieldInterface;
use Formward\Fields\Container;
use Formward\Fields\Input;
use Formward\Fields\Select;

class MailingAddressField extends Container
{
    protected $required = false;

    public function required($set = null, $clientSide = true)
    {
        if ($set !== null) {
            $required = [
                'address',
                'city',
                'state',
                'zip',
            ];
            $this->required = $set;
            foreach ($required as $c) {
                $this[$c]->required($set, $clientSide);
            }
        }
        return $this->required;
    }

    public function __construct(string $label, string $name = null, FieldInterface $parent = null)
    {
        parent::__construct($label, $name, $parent);
        $this['address'] = new Input('Address');
        $this['address']->addClass('address');
        $this['apartment'] = new Input('Apt/unit');
        $this['apartment']->addClass('apartment');
        $this['city'] = new Input('City');
        $this['city']->addClass('city');
        $this['state'] = new Select('State');
        $this['state']->options([
            "AL" => "Alabama",
            "AK" => "Alaska",
            "AZ" => "Arizona",
            "AR" => "Arkansas",
            "CA" => "California",
            "CO" => "Colorado",
            "CT" => "Connecticut",
            "DE" => "Delaware",
            "DC" => "District of Columbia",
            "FL" => "Florida",
            "GA" => "Georgia",
            "HI" => "Hawaii",
            "ID" => "Idaho",
            "IL" => "Illinois",
            "IN" => "Indiana",
            "IA" => "Iowa",
            "KS" => "Kansas",
            "KY" => "Kentucky",
            "LA" => "Louisiana",
            "ME" => "Maine",
            "MD" => "Maryland",
            "MA" => "Massachusetts",
            "MI" => "Michigan",
            "MN" => "Minnesota",
            "MS" => "Mississippi",
            "MO" => "Missouri",
            "MT" => "Montana",
            "NE" => "Nebraska",
            "NV" => "Nevada",
            "NH" => "New Hampshire",
            "NJ" => "New Jersey",
            "NM" => "New Mexico",
            "NY" => "New York",
            "NC" => "North Carolina",
            "ND" => "North Dakota",
            "OH" => "Ohio",
            "OK" => "Oklahoma",
            "OR" => "Oregon",
            "PA" => "Pennsylvania",
            "RI" => "Rhode Island",
            "SC" => "South Carolina",
            "SD" => "South Dakota",
            "TN" => "Tennessee",
            "TX" => "Texas",
            "UT" => "Utah",
            "VT" => "Vermont",
            "VA" => "Virginia",
            "WA" => "Washington",
            "WV" => "West Virginia",
            "WI" => "Wisconsin",
            "WY" => "Wyoming",
            "AS" => "American Samoa",
            "GU" => "Guam",
            "MP" => "Northern Mariana Islands",
            "PR" => "Puerto Rico",
            "VI" => "U.S. Virgin Islands",
        ]);
        $this['state']->default('NM');
        $this['state']->addClass('state');
        $this['zip'] = new Input('ZIP');
        $this['zip']->attr('pattern', '[0-9]{5}(-[0-9]{4})?');
        $this['zip']->addValidatorFunction('validzip', function ($field) {
            if (!$field->value()) {
                return true;
            }
            if (!preg_match('/^[0-9]{5}(-[0-9]{4})?$/', $field->value())) {
                return 'Please enter a valid USPS ZIP or ZIP+4 Code. It should look like ##### or #####-####';
            }
            return true;
        });
        $this['zip']->addClass('zip');
    }
}
