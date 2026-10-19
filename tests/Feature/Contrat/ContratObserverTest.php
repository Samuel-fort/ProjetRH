<?php

namespace Tests\Feature\Contrat;

use App\Enums\StatutContrat;
use App\Models\Contrat\Contrat;
use App\Models\Contrat\ContratHistorique;
use App\Models\User;
use App\Services\Contrat\ContratService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContratObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_ecrit_le_statut_initial_dans_lhistorique(): void
    {
        $contrat = Contrat::factory()->create();
        $historique = ContratHistorique::query()->where('contrat_id', $contrat->getKey())->firstOrFail();

        $this->assertNull($historique->ancien_statut);
        $this->assertSame(StatutContrat::Cree, $historique->nouveau_statut);
        $this->assertSame('cree', $historique->getRawOriginal('nouveau_statut'));
        $this->assertNull($historique->user_id);
    }

    public function test_signature_et_validation_ecrivent_les_transitions_dans_lordre(): void
    {
        $contrat = Contrat::factory()->create();
        $service = app(ContratService::class);

        $service->signer($contrat);
        $service->valider($contrat);

        $historiques = ContratHistorique::query()
            ->where('contrat_id', $contrat->getKey())
            ->orderBy('id')
            ->get();

        $this->assertSame([
            [null, StatutContrat::Cree],
            [StatutContrat::Cree, StatutContrat::Signe],
            [StatutContrat::Signe, StatutContrat::Valide],
        ], $historiques->map(fn (ContratHistorique $historique): array => [
            $historique->ancien_statut,
            $historique->nouveau_statut,
        ])->all());
    }

    public function test_modifier_une_colonne_sans_changer_le_statut_najoute_pas_dhistorique(): void
    {
        $contrat = Contrat::factory()->create();
        $nombreAvant = ContratHistorique::query()->where('contrat_id', $contrat->getKey())->count();

        $contrat->update(['lieu_travail' => 'Toamasina']);

        $this->assertSame($nombreAvant, ContratHistorique::query()->where('contrat_id', $contrat->getKey())->count());
    }

    public function test_utilisateur_authentifie_est_enregistre_dans_laudit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contrat = Contrat::factory()->create();

        $this->assertSame(
            $user->getKey(),
            ContratHistorique::query()->where('contrat_id', $contrat->getKey())->value('user_id')
        );
    }

    public function test_creation_sans_utilisateur_authentifie_enregistre_un_auteur_nul(): void
    {
        auth()->logout();

        $contrat = Contrat::factory()->create();

        $this->assertNull(
            ContratHistorique::query()->where('contrat_id', $contrat->getKey())->value('user_id')
        );
    }
}
