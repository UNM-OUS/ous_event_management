<?php
namespace Digraph\Modules\ous_event_management\Chunks;

use Digraph\Modules\ous_event_management\Signup;

/**
 * Chunks are used to organize the forms that make up a signup into
 * discrete chunks that can be requested by signup classes, signup
 * windows, events, and/or event groups.
 */
interface ChunkInterface
{
    const WEIGHT = 500;

    public function __construct(string $name, Signup $signup);
    public function body(): string;
    public function complete(): bool;
}
