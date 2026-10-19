<?php

namespace App\Exceptions\Contrat;

use DomainException;

class TransitionContratInvalide extends DomainException
{
    public function __construct(string $message = 'Cette transition du contrat n’est pas autorisée.')
    {
        parent::__construct($message);
    }
}
