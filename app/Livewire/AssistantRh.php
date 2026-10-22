<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\Assistant\AssistantService;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use Livewire\Component;
use Throwable;

class AssistantRh extends Component
{
    public bool $ouvert = false;

    public string $question = '';

    public ?string $erreur = null;

    #[Locked]
    #[Session('assistant-rh.messages')]
    public array $messages = [];

    public function toggle(): void
    {
        $this->ouvert = ! $this->ouvert;
    }

    public function effacerDiscussion(): void
    {
        $this->messages = [];
        $this->erreur = null;
    }

    public function envoyer(AssistantService $assistant): void
    {
        $this->erreur = null;
        $question = trim($this->question);

        if ($question === '') {
            return;
        }

        if (mb_strlen($question) > 500) {
            $this->erreur = 'Votre question doit contenir au maximum 500 caractères.';

            return;
        }

        $user = auth()->user();
        $key = 'assistant-rh:'.($user?->getAuthIdentifier() ?? 'inconnu');
        $limit = max(1, (int) config('assistant.rate_limit', 10));

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $this->erreur = 'La limite de questions est atteinte. Réessayez dans un instant.';

            return;
        }

        RateLimiter::hit($key, 60);

        if (! $user instanceof User) {
            $this->erreur = 'Votre session a expiré. Reconnectez-vous pour utiliser l’assistant.';

            return;
        }

        $conversation = [...$this->messages, ['role' => 'user', 'content' => $question]];
        $this->question = '';

        try {
            $reponse = $assistant->repondre($user, $this->pageCourante(), $conversation);
            $this->messages = array_slice([
                ...$conversation,
                ['role' => 'assistant', 'content' => $reponse],
            ], -10);
        } catch (Throwable $exception) {
            report($exception);
            $this->erreur = 'L’assistant est momentanément indisponible. Réessayez plus tard.';
        }
    }

    public function render()
    {
        return view('livewire.assistant-rh');
    }

    private function pageCourante(): string
    {
        $items = Filament::getCurrentPanel()?->getNavigationItems() ?? [];
        $active = collect($items)->first(
            fn (NavigationItem $item): bool => $item->isActive() || $item->isChildItemsActive(),
        );

        if (! $active) {
            return 'Tableau de bord';
        }

        $label = $active->getLabel();
        $label = $label instanceof Htmlable ? strip_tags($label->toHtml()) : $label;
        $group = $active->getGroup();
        $group = $group instanceof \UnitEnum ? $group->name : $group;

        return filled($group) ? "{$group} > {$label}" : (string) $label;
    }
}
