# Bloc 4 — Contrats et onboarding

## Périmètre

Le Bloc 4 gère les contrats successifs d'un salarié, leur cycle de vie, les affectations de poste, les documents associés et l'intégration administrative des nouvelles recrues. Les transitions métier sont exécutées par les services du domaine. Les ressources Filament du panneau `/admin` servent à consulter les données et à appeler ces services.

## Modèle de données

| Table | Colonnes principales | Clés étrangères | Unicité |
|---|---|---|---|
| `contrat_contrats` | `id`, `employe_id`, `type`, `date_debut`, nullable `date_fin`, `salaire`, `horaires`, `lieu_travail`, `statut`, nullable `date_signature`, nullable `date_validation`, nullable `contrat_precedent_id`, nullable `proposition_embauche_id`, timestamps | `employe_id` vers `embauche_employes`; `contrat_precedent_id` vers `contrat_contrats` (suppressions restreintes) | Un contrat suivant au plus par contrat précédent; `proposition_embauche_id` unique si renseigné |
| `contrat_affectations` | `id`, `employe_id`, `poste_id`, `departement_id`, `date_debut`, nullable `date_fin`, timestamps | Vers `embauche_employes`, `besoin_postes` et `besoin_departements` (suppressions restreintes) | Aucune contrainte unique |
| `contrat_onboardings` | `id`, `employe_id`, `contrat_id`, `statut`, nullable `date_fin`, timestamps | Vers `embauche_employes` et `contrat_contrats` (suppressions restreintes) | `employe_id` unique : un onboarding au plus par salarié |
| `contrat_onboarding_taches` | `id`, `onboarding_id`, `code`, `libelle`, `statut`, nullable `responsable_id`, nullable `date_echeance`, nullable `date_realisation`, timestamps | `onboarding_id` vers `contrat_onboardings` (suppression restreinte); nullable `responsable_id` vers `users` (mis à `null` à la suppression) | Couple (`onboarding_id`, `code`) unique |
| `contrat_documents` | `id`, `documentable_type`, `documentable_id`, `type`, `nom`, `chemin`, `disque`, nullable `televerse_par_id`, timestamps | Nullable `televerse_par_id` vers `users` (mis à `null` à la suppression); cible polymorphe sans clé étrangère | Index composite de la cible polymorphe, sans unicité |
| `contrat_contrat_historiques` | `id`, `contrat_id`, nullable `ancien_statut`, `nouveau_statut`, nullable `user_id`, nullable `created_at` | `contrat_id` vers `contrat_contrats` (suppression restreinte); nullable `user_id` vers `users` (mis à `null` à la suppression) | Aucune contrainte unique |
| `contrat_compteur_matricules` | `id`, `annee`, `dernier_numero`, timestamps | Aucune | `annee` unique |

Les références aux tables d'autres blocs (`embauche_employes`, `besoin_postes`, `besoin_departements` et `users`) sont explicites. La clé d'offre `proposition_embauche_id` reste non contrainte à une table du Bloc 3 tant que son contrat n'est pas intégré.

Relations principales :

```text
Employe 1 ── N Contrat
Employe 1 ── N Affectation
Employe 1 ── 0..1 Onboarding ── 1 Contrat
Onboarding 1 ── 6 OnboardingTache
Contrat 1 ── N Historique
Contrat N ── N Document (via relation polymorphe)
Employe N ── N Document (via relation polymorphe)
```

## Machine à états du contrat

| Transition | Effets de bord |
|---|---|
| `cree` vers `signe` | Enregistre la date de signature et attribue le matricule du salarié s'il n'en a pas déjà un. |
| `signe` vers `valide` | Enregistre la date de validation et crée l'onboarding avec ses six tâches. |

Les autres transitions ne sont pas autorisées. Un contrat `signe` ou `valide` est immuable via le service de modification. Le renouvellement crée un nouveau contrat et ne modifie pas le précédent. Chaque changement de statut est ajouté à `contrat_contrat_historiques`; les seeders écrivent cet historique explicitement parce qu'ils désactivent les événements Eloquent.

## Renouvellement

Un contrat est renouvelable s'il est `valide`, possède une date de fin et n'a pas déjà de contrat suivant. Le service crée un nouveau contrat de type `renouvellement`, au statut `cree`, rattaché au même salarié et lié à sa source. La date de début doit être postérieure à la date de fin précédente. Le salaire, les horaires et le lieu sont repris de la source si aucune nouvelle donnée correspondante n'est fournie. Le service ne signe ni ne valide le nouveau contrat et ne change aucune donnée du contrat source.

## Génération du matricule

Le format est `EMP-AAAA-NNNN`, par exemple `EMP-2026-0001`. Le compteur est isolé par année dans `contrat_compteur_matricules` et verrouillé pendant son incrémentation. L'attribution a lieu au moment de la signature; un salarié qui possède déjà un matricule le conserve, rendant l'opération idempotente.

## Onboarding

La checklist comporte les tâches suivantes :

| Tâche | Échéance par défaut |
|---|---|
| Contrat signé | Date de signature |
| Badge | Date de début du contrat |
| Compte informatique | Date de début du contrat |
| Matériel | Date de début du contrat |
| Formation | Date de début + 14 jours |
| Inscription à la paie | Date de début + 7 jours |

La tâche « Contrat signé » est créée comme faite. L'onboarding passe à `termine` uniquement lorsqu'il contient au moins une tâche et que toutes ses tâches sont au statut `fait`; toute réouverture d'une tâche le ramène à `en_cours`.

## Documents

Les cibles supportées sont un contrat ou un salarié. Les fichiers téléversés par le service sont stockés sur le disque `local`; les documents créés à partir d'un chemin préexistant ne créent pas de fichier. Les extensions autorisées sont PDF, JPG, JPEG et PNG, pour une taille maximale de 10 Mo. Le téléchargement vérifie que le fichier référencé existe.

## Autorisations

| Action | Rôles autorisés |
|---|---|
| Voir les contrats | `rh`, `admin`, `direction`, `finance` |
| Créer, modifier (si le contrat est `cree`), signer, valider ou renouveler (si renouvelable) un contrat | `rh`, `admin` |
| Voir les affectations et les onboardings | `rh`, `admin`, `direction`, `manager` |
| Créer ou modifier une affectation | `rh`, `admin` |
| Créer, modifier ou supprimer un onboarding depuis l'interface | Aucun rôle; opérations prises en charge par les services |
| Modifier une tâche d'onboarding | `rh`, `admin`, ou son responsable assigné |
| Voir ou créer un document | `rh`, `admin` |
| Supprimer ou modifier un document | Aucun rôle |
| Supprimer un contrat ou une affectation | Aucun rôle |

La colonne `users.role` et l'enum partagé `RoleUtilisateur` sont disponibles. Le trait de résolution refuse un rôle absent, nul ou hors de la liste autorisée.

## Intégrations avec les autres blocs

- **Bloc 3 — `PropositionAcceptee` :** le listener `CreerContratApresAcceptation` attend l'événement `App\Events\PropositionAcceptee` avec `proposition` et `employe`. Les noms et relations réels restent à confirmer lors de la fusion du Bloc 3.
- **Bloc 3 — `GenererContratPdf` :** le service de contrat recherche `App\Services\Embauche\GenererContratPdf` après la création issue d'une proposition. L'intégration, sa signature et son résultat sont à confirmer avec le Bloc 3.
- **Bloc 3 — `PossedeContrats` :** ce trait doit être conservé/intégré au modèle `Employe` pour exposer contrats et affectations; le modèle actuellement présent est un stub de coordination.
- **Point d'adaptation `DonneesContratDepuisProposition` :** cette classe centralise la lecture des attributs de proposition, de la candidature, de l'offre et de la demande de recrutement. Elle est le seul point à adapter si les contrats d'interface réels diffèrent.

## Parcours de démonstration dans `/admin`

1. Ouvrir **Contrats** et créer un CDD avec salarié, dates, salaire, horaires et lieu de travail.
2. Sur sa fiche, signer le contrat; vérifier la date, le matricule affiché et la ligne d'historique.
3. Valider le contrat; ouvrir l'onboarding créé et constater ses six tâches.
4. Assigner une tâche à un utilisateur, puis la marquer comme faite; terminer les tâches restantes pour constater le statut `termine`.
5. Consulter l'historique des affectations du salarié, puis créer une nouvelle affectation pour constater la clôture de la précédente.
6. Pour illustrer le renouvellement, ouvrir un contrat CDD valide échu et renouvelable, puis créer son contrat suivant depuis l'action **Renouveler**.
7. Ouvrir **Documents** pour consulter les pièces jointes; les chemins du seeder sont des exemples et ne correspondent pas à des fichiers téléchargeables.
