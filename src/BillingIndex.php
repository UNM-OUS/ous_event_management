<?php
namespace Digraph\Modules\ous_event_management;

use Digraph\DSO\Noun;

/**
 * This class is used to define the billing indexes used to generate
 * reports regarding billing of things like regalia to departments.
 */
class BillingIndex extends Noun
{
    const ROUTING_NOUNS = ['event-billing-index'];
    const SLUG_ENABLED = false;

    // public function name($verb = null)
    // {
    // }

    public function parentEdgeType(Noun $parent): ?string
    {
        return 'event-billing-index';
    }
}
