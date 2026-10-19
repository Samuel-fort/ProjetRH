<?php

namespace App\Notifications\Contrat;

use App\Models\Contrat\OnboardingTache;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TacheOnboardingAssignee extends Notification
{
    use Queueable;

    public function __construct(private readonly OnboardingTache $tache) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employe = $this->tache->onboarding->employe;
        $nomEmploye = trim("{$employe->prenom} {$employe->nom}");
        $echeance = $this->tache->date_echeance?->format('d/m/Y') ?? 'Non définie';

        return (new MailMessage)
            ->subject('Nouvelle tâche d’onboarding : '.$this->tache->libelle)
            ->greeting('Bonjour,')
            ->line("Une nouvelle tâche d’onboarding vous a été attribuée pour {$nomEmploye}.")
            ->line('Tâche : '.$this->tache->libelle)
            ->line('Échéance : '.$echeance)
            ->line('Merci de réaliser cette tâche dans le délai prévu.');
    }
}
