<?php

namespace Tests\Feature\Recrutement;

use App\Models\Recrutement\Candidature;
use App\Services\Recrutement\PreselectionCvService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PreselectionCvServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enregistre_une_analyse_structuree_sur_la_candidature(): void
    {
        $candidature = Candidature::factory()->create();
        $analyse = $this->analyse();

        $resultat = app(PreselectionCvService::class)->enregistrer($candidature, $analyse);

        $this->assertSame($analyse, $resultat->analyse_preselection);
        $this->assertSame($analyse, $candidature->fresh()->analyse_preselection);
    }

    public function test_refuse_un_score_hors_limites(): void
    {
        $this->expectException(DomainException::class);

        app(PreselectionCvService::class)->enregistrer(
            Candidature::factory()->create(),
            [...$this->analyse(), 'score' => 101],
        );
    }

    public function test_refuse_une_recommandation_non_prevue(): void
    {
        $this->expectException(DomainException::class);

        app(PreselectionCvService::class)->enregistrer(
            Candidature::factory()->create(),
            [...$this->analyse(), 'recommandation' => 'excellent'],
        );
    }

    public function test_interroge_le_fournisseur_ia_et_enregistre_sa_reponse(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.base_url' => 'https://ia.test/v1',
            'services.openai.model' => 'test-model',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://ia.test/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode($this->analyse(), JSON_THROW_ON_ERROR)],
                ]],
            ]),
        ]);

        $candidature = Candidature::factory()->create();
        $candidature->candidat->update(['cv_texte' => 'Developpeur Laravel depuis 3 ans.']);

        $resultat = app(PreselectionCvService::class)->analyserAvecIa($candidature);

        $this->assertSame($this->analyse(), $resultat->analyse_preselection);
        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization')
            && str_contains($request['messages'][1]['content'], 'Developpeur Laravel depuis 3 ans.'));
    }

    private function analyse(): array
    {
        return [
            'recommandation' => 'a_contacter_en_priorite',
            'score' => 88,
            'confiance' => 'elevee',
            'resume' => 'Experience Laravel et gestion d’equipe.',
            'criteres' => [[
                'critere' => 'Laravel', 'type' => 'indispensable', 'statut' => 'oui', 'preuve' => '3 ans de Laravel',
            ]],
            'points_forts' => ['Laravel'],
            'points_de_vigilance' => [],
            'questions_entretien' => ['Quel projet Laravel avez-vous livre ?'],
            'alertes' => [],
        ];
    }
}
