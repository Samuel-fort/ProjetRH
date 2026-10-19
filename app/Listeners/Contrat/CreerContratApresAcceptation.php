<?php

namespace App\Listeners\Contrat;

use App\Events\PropositionAcceptee;
use App\Services\Contrat\ContratService;
use App\Services\Contrat\DonneesContratDepuisProposition;

class CreerContratApresAcceptation
{
    public function __construct(
        private readonly DonneesContratDepuisProposition $donneesContratDepuisProposition,
        private readonly ContratService $contratService
    ) {}

    public function handle(PropositionAcceptee $event): void
    {
        $donnees = $this->donneesContratDepuisProposition->extraire($event->proposition);

        $this->contratService->creerDepuisProposition($event->employe, $donnees);
    }
}
