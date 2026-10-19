<?php

namespace Tests\Feature\Recrutement;

use App\Models\Recrutement\Candidature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tests du Bloc 2 : qui a le droit de faire quoi. */
class CandidaturePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_droits_dependent_du_role(): void
    {
        $candidature = Candidature::factory()->create();
        $role = fn (string $r) => User::factory()->create(['role' => $r]);

        // Décision finale : RH et Direction oui ; manager et finance non.
        $this->assertTrue($role('rh')->can('decider', $candidature));
        $this->assertTrue($role('direction')->can('decider', $candidature));
        $this->assertFalse($role('manager')->can('decider', $candidature));
        $this->assertFalse($role('finance')->can('decider', $candidature));

        // Évaluer : le manager oui, la finance non.
        $this->assertTrue($role('manager')->can('evaluer', $candidature));
        $this->assertFalse($role('finance')->can('evaluer', $candidature));

        // Un utilisateur sans rôle ne voit rien.
        $this->assertFalse(User::factory()->create()->can('view', $candidature));
    }
}
