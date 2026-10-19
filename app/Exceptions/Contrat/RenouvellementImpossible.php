<?php

namespace App\Exceptions\Contrat;

use DomainException;

class RenouvellementImpossible extends DomainException
{
    public function __construct(string $message = 'Ce contrat ne peut pas être renouvelé.')
    {
        parent::__construct($message);
    }
}
