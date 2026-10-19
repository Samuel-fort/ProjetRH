<?php

namespace Tests\Feature\Contrat;

use App\Models\Contrat\OnboardingTache;
use App\Models\User;
use App\Notifications\Contrat\TacheOnboardingAssignee;
use App\Services\Contrat\OnboardingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigner_une_tache_notifie_le_responsable_selectionne(): void
    {
        Notification::fake();
        $responsable = User::factory()->create();
        $tache = OnboardingTache::factory()->create();

        app(OnboardingService::class)->assignerTache(
            $tache,
            $responsable->getKey(),
            CarbonImmutable::parse('2026-08-01')
        );

        Notification::assertSentTo($responsable, TacheOnboardingAssignee::class);
    }

    public function test_retirer_le_responsable_nenvoie_aucune_notification(): void
    {
        Notification::fake();
        $tache = OnboardingTache::factory()->create();

        app(OnboardingService::class)->assignerTache($tache, null);

        Notification::assertNothingSent();
    }

    public function test_notification_contient_le_salarie_la_tache_et_lecheance(): void
    {
        $tache = OnboardingTache::factory()->create([
            'libelle' => 'Créer le compte informatique',
            'date_echeance' => '2026-08-01',
        ]);
        $notification = new TacheOnboardingAssignee($tache);
        $mail = $notification->toMail(User::factory()->make());

        $this->assertSame('Nouvelle tâche d’onboarding : Créer le compte informatique', $mail->subject);
        $this->assertContains('Tâche : Créer le compte informatique', $mail->introLines);
        $this->assertContains('Échéance : 01/08/2026', $mail->introLines);
        $this->assertStringContainsString(
            'pour '.$tache->onboarding->employe->prenom.' '.$tache->onboarding->employe->nom.'.',
            $mail->introLines[0]
        );
    }
}
