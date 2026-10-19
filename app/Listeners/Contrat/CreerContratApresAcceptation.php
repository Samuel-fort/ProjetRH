<?php

namespace App\Listeners\Contrat;

use App\Events\PropositionAcceptee;
use App\Services\Contrat\ContratService;
use App\Services\Contrat\DonneesContratDepuisProposition;
use Illuminate\Validation\ValidationException;

class CreerContratApresAcceptation
{
    public function __construct(
        private readonly DonneesContratDepuisProposition $donneesContratDepuisProposition,
        private readonly ContratService $contratService
    ) {}

    public function handle(PropositionAcceptee $event): void
    {
        $donnees = $this->donneesContratDepuisProposition->extraire($event->proposition);

        try {
            $this->contratService->creerDepuisProposition($event->proposition->employe, $donnees);
        } catch (ValidationException $exception) {
            report($exception);
        }
    }
}
