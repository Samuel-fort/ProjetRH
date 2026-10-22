<?php

namespace App\Services\Assistant;

use App\Models\User;
use DomainException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class AssistantService
{
    /**
     * @param  array<int, array{role: string, content: string}>  $conversation
     */
    public function repondre(User $user, string $page, array $conversation): string
    {
        if (config('assistant.fake')) {
            return 'Mode simulation : ouvrez le menu indique dans le guide pour avancer dans le workflow RH.';
        }

        $apiKey = config('services.openai.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new DomainException('Le service assistant n’est pas configuré.');
        }

        $messages = [
            ['role' => 'system', 'content' => $this->promptSysteme($user, $page)],
            ...array_slice($conversation, -10),
        ];

        try {
            $payload = Http::withToken($apiKey)
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(45)
                ->post(rtrim((string) config('services.openai.base_url'), '/').'/chat/completions', [
                    'model' => config('services.openai.model'),
                    'temperature' => 0.2,
                    'messages' => $messages,
                ])
                ->throw()
                ->json();
        } catch (ConnectionException|RequestException $exception) {
            throw new DomainException('Le service assistant est indisponible pour le moment.', previous: $exception);
        }

        $content = $payload['choices'][0]['message']['content'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            throw new DomainException('Le service assistant n’a pas fourni de réponse.');
        }

        return $this->retirerPensees($content);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $conversation
     */
    private function promptSysteme(User $user, string $page): string
    {
        $guide = File::get(resource_path('assistant/guide.md'));
        $role = $user->role?->value ?? 'non précisé';

        return "Tu es l’assistant de navigation d’un panneau RH.\n"
            .'Réponds en français, en quelques phrases courtes et concrètes. '
            .'Indique le menu à ouvrir quand c’est utile, par exemple « Recrutement > Candidatures ». '
            .'Ne prétends jamais accéder à la base de données et n’invente aucune fonctionnalité. '
            .'Si l’information ne figure pas dans le guide, réponds « Je ne sais pas ». '
            .'Tu expliques uniquement le workflow, les statuts, les rôles et où cliquer. '
            .'Tu ne prends jamais de décision de recrutement et ne modifies aucune donnée.\n\n'
            ."Role de l’utilisateur : {$role}\n"
            ."Page courante : {$page}\n\n"
            .'Guide du projet :\n'.$guide;
    }

    private function retirerPensees(string $content): string
    {
        $content = preg_replace('/<think\b[^>]*>.*?<\/think>/is', '', $content) ?? $content;

        return trim($content);
    }
}
