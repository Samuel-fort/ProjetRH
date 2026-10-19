<?php

namespace App\Exceptions\Contrat;

use DomainException;

class DocumentNonAutorise extends DomainException
{
    public function __construct(string $message = 'Ce document ou cette cible n’est pas autorisé.')
    {
        parent::__construct($message);
    }
}
