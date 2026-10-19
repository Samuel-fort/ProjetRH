<?php

namespace App\Services\Embauche;

use App\Models\Embauche\PropositionEmbauche;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Génère le PDF du contrat (salaire, horaires, lieu de travail, dates).
 *
 * Nécessite le paquet `barryvdh/laravel-dompdf` :  composer require barryvdh/laravel-dompdf
 * (ajout à coordonner avec Samuel : composer.json est un fichier partagé).
 *
 * Aujourd'hui il part des données de la PropositionEmbauche (le modèle Contrat du Bloc 4 n'existe pas
 * encore). Quand il existera, il suffira d'ajouter une méthode depuisContrat() qui appelle
 * le même gabarit `pdf.contrat` avec les données du contrat.
 */
class GenererContratPdf
{
    /** Génère le PDF, l'enregistre dans storage/app/private/contrats et mémorise son chemin. */
    public function depuisProposition(PropositionEmbauche $proposition): string
    {
        $proposition->loadMissing(['candidature.candidat', 'candidature.offreEmploi', 'employe']);

        $html = view('pdf.contrat', ['d' => $this->donnees($proposition)])->render();

        $matricule = $proposition->employe?->matricule ?? 'sans-matricule';
        $chemin = "contrats/contrat-{$matricule}-{$proposition->id}.pdf";

        Storage::disk('local')->put($chemin, $this->rendrePdf($html));
        $proposition->update(['contrat_pdf_path' => $chemin]);

        return $chemin;
    }

    /** Les informations affichées dans le contrat. */
    public function donnees(PropositionEmbauche $proposition): array
    {
        $candidat = $proposition->candidature->candidat;

        return [
            'employeur' => config('app.name'),
            'salarie' => $candidat->nom_complet,
            'email' => $candidat->email,
            'matricule' => $proposition->employe?->matricule,
            'poste' => $proposition->candidature->offreEmploi->titre,
            'salaire' => number_format((float) $proposition->salaire, 0, ',', ' '),
            'horaires' => $proposition->horaires,
            'lieu_travail' => $proposition->lieu_travail,
            'date_debut' => $proposition->date_debut->format('d/m/Y'),
            'date_edition' => now()->format('d/m/Y'),
        ];
    }

    /** Transforme le HTML en PDF. Isolé dans sa propre méthode pour pouvoir la remplacer dans les tests. */
    protected function rendrePdf(string $html): string
    {
        if (! class_exists(Pdf::class)) {
            throw new RuntimeException('Paquet manquant : exécutez « composer require barryvdh/laravel-dompdf ».');
        }

        return Pdf::loadHTML($html)->setPaper('a4')->output();
    }
}
