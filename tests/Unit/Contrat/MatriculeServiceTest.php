<?php

namespace Tests\Unit\Contrat;

use App\Models\Contrat\CompteurMatricule;
use App\Models\Embauche\Employe;
use App\Services\Contrat\MatriculeService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_premier_matricule_respecte_le_format_annuel(): void
    {
        $employe = Employe::factory()->create();

        $matricule = app(MatriculeService::class)->attribuer($employe, CarbonImmutable::parse('2026-05-10'));

        $this->assertSame('EMP-2026-0001', $matricule);
        $this->assertSame('EMP-2026-0001', $employe->fresh()->matricule);
    }

    public function test_les_employes_successifs_recoivent_des_numeros_croissants(): void
    {
        $service = app(MatriculeService::class);
        $premier = Employe::factory()->create();
        $second = Employe::factory()->create();

        $this->assertSame('EMP-2026-0001', $service->attribuer($premier, CarbonImmutable::parse('2026-03-01')));
        $this->assertSame('EMP-2026-0002', $service->attribuer($second, CarbonImmutable::parse('2026-03-01')));
    }

    public function test_attribuer_est_idempotent_pour_un_employe(): void
    {
        $employe = Employe::factory()->create();
        $service = app(MatriculeService::class);
        $date = CarbonImmutable::parse('2026-03-01');

        $premier = $service->attribuer($employe, $date);
        $second = $service->attribuer($employe, $date);

        $this->assertSame($premier, $second);
        $this->assertSame(1, CompteurMatricule::query()->where('annee', 2026)->value('dernier_numero'));
    }

    public function test_le_compteur_repart_a_un_pour_une_nouvelle_annee(): void
    {
        $service = app(MatriculeService::class);
        $employe2026 = Employe::factory()->create();
        $employe2027 = Employe::factory()->create();

        $this->assertSame('EMP-2026-0001', $service->attribuer($employe2026, CarbonImmutable::parse('2026-12-31')));
        $this->assertSame('EMP-2027-0001', $service->attribuer($employe2027, CarbonImmutable::parse('2027-01-01')));
    }

    public function test_aucun_matricule_nest_reutilise_entre_employes(): void
    {
        $service = app(MatriculeService::class);
        $employes = Employe::factory()->count(3)->create();
        $matricules = $employes
            ->map(fn (Employe $employe): string => $service->attribuer($employe, CarbonImmutable::parse('2026-03-01')))
            ->all();

        $this->assertCount(3, array_unique($matricules));
        $this->assertSame(3, CompteurMatricule::query()->where('annee', 2026)->value('dernier_numero'));
    }
}
