<?php

namespace Tests\Unit\Contrat;

use App\Enums\StatutContrat;
use App\Enums\TypeContrat;
use App\Enums\TypeTacheOnboarding;
use PHPUnit\Framework\TestCase;

class EnumsContratTest extends TestCase
{
    public function test_statut_contrat_autorise_uniquement_les_transitions_sequentielles(): void
    {
        foreach (StatutContrat::cases() as $source) {
            foreach (StatutContrat::cases() as $cible) {
                $attendu = ($source === StatutContrat::Cree && $cible === StatutContrat::Signe)
                    || ($source === StatutContrat::Signe && $cible === StatutContrat::Valide);

                $this->assertSame($attendu, $source->peutPasserA($cible));
            }
        }
    }

    public function test_type_contrat_indique_si_la_duree_est_determinee(): void
    {
        foreach (TypeContrat::cases() as $type) {
            $this->assertSame($type !== TypeContrat::Cdi, $type->aDureeDeterminee());
        }
    }

    public function test_types_de_taches_sont_ordonnees_pour_la_checklist(): void
    {
        $this->assertSame([
            TypeTacheOnboarding::ContratSigne,
            TypeTacheOnboarding::Badge,
            TypeTacheOnboarding::Compte,
            TypeTacheOnboarding::Materiel,
            TypeTacheOnboarding::Formation,
            TypeTacheOnboarding::InscriptionPaie,
        ], TypeTacheOnboarding::ordonnees());
        $this->assertCount(6, TypeTacheOnboarding::ordonnees());
    }
}
