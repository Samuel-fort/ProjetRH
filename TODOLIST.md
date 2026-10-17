# TODOLIST — SIRH (Laravel + PostgreSQL + Filament)

Ity ny **bases** de chaque bloc. Ny amélioration am farany rehefa milamina

| Personne | Bloc | Branche |
|----------|------|---------|
| **Samuel**  | Bloc 1 — Expression du besoin | `bloc1-besoin` |
| **Lionel** | Bloc 2 — Recrutement + Bloc 3 — Sélection et embauche | `bloc2-recrutement`, `bloc3-embauche` |
| **Voara** | Bloc 4 — Contrat et onboarding | `bloc4-contrat` |

Cocheo x ze vita 

---

## 0. Règles Git

 **une branche par bloc**.
 **Lionel_Zavatranatao**......

1. `main` contient toujours un projet qui démarre (`./install.sh` puis `./lancer.sh`).
2. Chacun travaille sur sa branche :
   ```bash
   git checkout main && git pull
   git checkout -b bloc1-besoin
   ```
3. Commits petits et fréquents, message clair (`bloc1: migration departements`).
4. **Pousser et fusionner souvent** (au moins une fois par jour de travail) via une Pull Request vers `main`, relue par un des deux autres. Des petites PR créent peu de conflits, une grosse PR en crée beaucoup.
5. **Avant de continuer à coder, récupérer le travail des autres :**
   ```bash
   git checkout main && git pull
   git checkout bloc1-besoin && git merge main     # ou : git rebase main
   php artisan migrate
   ```
6. **Ne jamais commiter** : `.env`, `vendor/`, `node_modules/`, `database/database.sqlite`.
7. **Ne jamais modifier une migration déjà fusionnée dans `main`** : créer une nouvelle migration (`php artisan make:migration ajoute_xxx_a_yyy`).
8. Si un conflit apparaît, on ne l'écrase pas à l'aveugle : on appelle l'autre personne.

### Chacun reste dans son dossier
La structure du projet est déjà découpée par bloc. **On ne touche que ses propres dossiers :**

| Bloc | Dossiers |
|------|----------|
| 1 | `Models/Besoin`, `Services/Besoin`, `Filament/Resources/Besoin`, `BesoinSeeder` |
| 2 | `Models/Recrutement`, `Services/Recrutement`, `Filament/Resources/Recrutement`, `RecrutementSeeder` |
| 3 | `Models/Embauche`, `Services/Embauche`, `Filament/Resources/Embauche`, `EmbaucheSeeder` |
| 4 | `Models/Contrat`, `Services/Contrat`, `Filament/Resources/Contrat`, `ContratSeeder` |

### Fichiers partagés (risque de conflit) : Samuel en est responsable
`routes/web.php`, `routes/api.php`, `app/Models/User.php`, `app/Providers/Filament/AdminPanelProvider.php`, `DatabaseSeeder.php`, `composer.json`, `package.json`.
**Si tu as besoin d'une modification dans l'un d'eux, demande à Samuel** (ou fais un tout petit commit isolé, poussé immédiatement).

### Ordre des migrations : préfixes fixes
Laravel exécute les migrations par ordre alphabétique des noms de fichiers. Pour que les FK fonctionnent et que les horodatages ne se mélangent pas, **on renomme chaque migration avec le préfixe de son bloc** :

| Bloc | Préfixe |
|------|---------|
| 0 (socle) | `2026_10_16_0NNNNN_` |
| 1 | `2026_10_16_1NNNNN_` |
| 2 | `2026_10_16_2NNNNN_` |
| 3 | `2026_10_16_3NNNNN_` |
| 4 | `2026_10_16_4NNNNN_` |

Exemple : `2026_10_16_100001_create_besoin_departements_table.php`, puis `..._100002_create_besoin_postes_table.php`, etc. (le `NNNNN` s'incrémente dans l'ordre des dépendances à l'intérieur du bloc).

### Conventions communes
- Noms de tables en snake_case pluriel, **préfixés par leur bloc**, noms de colonnes en français :

  | Bloc | Préfixe de table | Exemple |
  |------|------------------|---------|
  | 1 | `besoin_` | `besoin_postes` |
  | 2 | `recrutement_` | `recrutement_candidats` |
  | 3 | `embauche_` | `embauche_employes` |
  | 4 | `contrat_` | `contrat_contrats` |

  Les tables standard de Laravel (`users`, `cache`, `jobs`, `sessions`…) gardent leur nom, sans préfixe.
- **Chaque modèle déclare son nom de table** (`protected $table = 'besoin_postes';`), sinon Laravel cherche le nom anglais par défaut et ça plante.
- Statuts = **Enums PHP** (`app/Enums/`), un fichier par enum, nom préfixé par le bloc si besoin (`StatutDemande`, `StatutCandidature`, `TypeContrat`…).
- Chaque modèle a sa factory et chaque bloc a son seeder pour avoir des données de test identiques chez tous.
- Clés étrangères : `foreignId('...')->constrained('nom_de_la_table')` en **donnant toujours le nom de la table** (Laravel ne devine pas les préfixes). Pas de `cascadeOnDelete` sans en parler : on garde l'historique.

---

## 1. Phase 0 — Socle (Samuel, **en premier**, avant que les autres commencent)

À faire par Samuel sur `main` (ou sur une branche `socle` fusionnée tout de suite) :

- [ ] Pousser le projet Laravel + Filament complet sur `main` (voir « Avant de pousser » ci-dessous)
- [ ] Vérifier avec un clone vierge : `git clone ... /tmp/test-rh && cd /tmp/test-rh && ./install.sh`
- [ ] `.env.example` pré-rempli avec `DB_CONNECTION=pgsql`, `DB_DATABASE=rh_sirh`, `DB_USERNAME=rh_user`
- [ ] Supprimer le fichier `br` du dépôt (`git rm br`), mettre le mot de passe local dans le README
- [ ] README : prérequis, installation (`prerequis.sh` → `install.sh` → `lancer.sh`), règles Git
- [ ] Ajouter la colonne `role` à `users` (Enum `RoleUtilisateur` : `manager`, `rh`, `finance`, `direction`, `admin`)
- [ ] Seeder d'utilisateurs de test, un par rôle (`manager@sirh.test`, `rh@sirh.test`, `finance@sirh.test`, `direction@sirh.test`, mot de passe `password`)
- [ ] `DatabaseSeeder` qui appelle `BesoinSeeder`, `RecrutementSeeder`, `EmbaucheSeeder`, `ContratSeeder` (fichiers vides au départ pour éviter les conflits)
- [ ] Créer les 4 fichiers `*Seeder.php` vides et les 4 dossiers `Filament/Resources/<Bloc>` (avec un `.gitkeep`)
- [ ] Prévenir Lionel et Voara que le socle est sur `main`

### Avant de pousser (Samuel)
```bash
git status                  # .env ne doit PAS apparaître
git add -A
git commit -m "socle: Laravel + Filament + scripts d'installation"
git push
```

---

## 2. Contrat d'interface entre les blocs

Pour travailler en parallèle sans se bloquer, **on se met d'accord dès maintenant** sur les tables et clés étrangères. Les colonnes secondaires peuvent évoluer dans son propre bloc, mais **les noms de tables et les colonnes marquées ★ ne changent pas sans prévenir.**

### Bloc 1 (Samuel)
| Table | Colonnes principales |
|-------|----------------------|
| `besoin_departements` | id, nom ★, code, `parent_id` (nullable, auto-référence : département → service → section) |
| `besoin_postes` | id ★, intitule, `departement_id` ★, description. **Aucune FK vers un employé** |
| `besoin_demande_recrutements` | id ★, `poste_id` ★, `departement_id`, `demandeur_id` (users), nombre, motif (`creation`/`remplacement`), budget, date_souhaitee, statut ★ |
| `besoin_validations` | id, `demande_recrutement_id`, etape (`manager`/`rh`/`finance`/`direction`), `validateur_id`, statut, commentaire, date_decision |

### Bloc 2 (Lionel)
| Table | Colonnes principales |
|-------|----------------------|
| `recrutement_offre_emplois` | id ★, `demande_recrutement_id` ★, titre, description, statut, date_publication, date_cloture |
| `recrutement_candidats` | id ★, nom, prenom, email (unique), telephone, cv_path |
| `recrutement_candidatures` | id ★, `offre_emploi_id`, `candidat_id` ★, statut ★, date_candidature. Unique (`offre_emploi_id`, `candidat_id`) |
| `recrutement_entretiens` | id, `candidature_id`, type (`dossier`/`entretien`/`test`), date_heure, lieu, `intervenant_id` |
| `recrutement_evaluation_candidats` | id, `candidature_id`, `entretien_id` (nullable), `evaluateur_id`, note, commentaire |
| `recrutement_candidature_historiques` | id, `candidature_id`, ancien_statut, nouveau_statut, `user_id`, created_at (**traçabilité**) |

### Bloc 3 (Lionel)
| Table | Colonnes principales |
|-------|----------------------|
| `embauche_proposition_embauches` | id ★, `candidature_id` ★, salaire, horaires, lieu_travail, date_debut, statut (`envoyee`/`acceptee`/`refusee`), date_reponse |
| `embauche_employes` | id ★, `candidat_id` ★ (nullable si import), `matricule` (nullable, unique), nom, prenom, email, statut. **Pas de colonne `type_contrat`** |

### Bloc 4 (Voara)
| Table | Colonnes principales |
|-------|----------------------|
| `contrat_contrats` | id ★, `employe_id` ★, type (`cdd`/`cdi`/`renouvellement`…), date_debut, date_fin, salaire, horaires, lieu_travail, statut (`cree`/`signe`/`valide`), `contrat_precedent_id` (nullable, historique) |
| `contrat_affectations` | id, `employe_id`, `poste_id`, `departement_id`, date_debut, date_fin (nullable) |
| `contrat_onboardings` | id, `employe_id`, `contrat_id`, statut |
| `contrat_onboarding_taches` | id, `onboarding_id`, libelle (contrat signé, badge, compte, matériel, formation, inscription paie), statut, `responsable_id`, date_echeance |
| `contrat_documents` | id, relation polymorphe (`documentable_type`, `documentable_id`), type, nom, chemin |

### Décisions à confirmer ensemble
- **Quand naît le matricule ?** Le cours Bloc 3 dit « après acceptation », le Bloc 4 dit « une fois le contrat signé ». **Proposition : l'`Employe` est créé à l'acceptation (sans matricule), et le matricule est généré par Voara au passage du contrat à `signe`.** À valider avec le prof si besoin.
- **Qui crée le contrat ?** Lionel ne touche pas à la table `contrat_contrats`. À l'acceptation d'une proposition, il envoie l'événement `PropositionAcceptee` (dans `app/Events/`, créé par Lionel). **Voara écoute cet événement** (listener dans `Services/Contrat`) : il crée le `Contrat` (statut `cree`) et l'`Affectation`.
- **Qui génère le PDF du contrat ?** Voara l'appelle après la création du contrat. Le service `GenererContratPdf` est écrit par Lionel (Bloc 3 du cours) et prend un `Contrat`. En attendant que `Contrat` existe, Lionel le teste avec les données de `PropositionEmbauche`.

---

## 3. Bloc 1 — Expression du besoin (Samuel) · branche `bloc1-besoin`

**Rappel de conception : un Poste existe indépendamment de l'Employé.**

### Données
- [ ] Migration + modèle + factory `Departement` (hiérarchie via `parent_id`)
- [ ] Migration + modèle + factory `Poste`
- [ ] Migration + modèle + factory `DemandeRecrutement`
- [ ] Migration + modèle + factory `Validation`
- [ ] Enums : `MotifDemande` (`creation`, `remplacement`), `StatutDemande` (`brouillon`, `en_validation`, `validee`, `refusee`, `recrutement_ouvert`), `EtapeValidation`, `StatutValidation`
- [ ] Relations Eloquent dans les 4 modèles (`hasMany`, `belongsTo`)
- [ ] `BesoinSeeder` : quelques départements, postes et demandes de test à différents statuts

### Workflow : Manager → RH → Finance → Direction → ouverture du recrutement
- [ ] `Services/Besoin/DemandeRecrutementService` : `soumettre()`, `valider()`, `refuser()`
- [ ] À la soumission : créer les 4 lignes de `besoin_validations` (une par étape, dans l'ordre)
- [ ] Une étape ne peut être validée que si la précédente l'est et que l'utilisateur a le bon rôle
- [ ] Un refus à n'importe quelle étape passe la demande à `refusee` (avec commentaire obligatoire)
- [ ] Quand la Direction valide : la demande passe à `recrutement_ouvert` (c'est le signal pour le Bloc 2)
- [ ] Policy `DemandeRecrutementPolicy` (qui voit quoi, qui peut valider quelle étape)
- [ ] Observer pour enregistrer l'historique des changements de statut
- [ ] Notification au validateur suivant à chaque étape

### Écrans Filament (`Filament/Resources/Besoin`)
- [ ] Resource `Departement`
- [ ] Resource `Poste`
- [ ] Resource `DemandeRecrutement` (formulaire manager, boutons Valider / Refuser selon le rôle)
- [ ] Affichage de la progression des validations dans la demande

### Tests
- [ ] Test : une demande ne passe pas à `recrutement_ouvert` sans les 4 validations
- [ ] Test : un rôle non autorisé ne peut pas valider une étape

---

## 4. Bloc 2 — Recrutement (Lionel) · branche `bloc2-recrutement`

**Dépend de :** `besoin_demande_recrutements` (Bloc 1). En attendant que Samuel ait fusionné, utiliser `main` après le socle et demander à Samuel de pousser tôt la migration de `besoin_demande_recrutements`.

### Données
- [ ] Migration + modèle + factory `OffreEmploi`
- [ ] Migration + modèle + factory `Candidat`
- [ ] Migration + modèle + factory `Candidature` (contrainte d'unicité offre + candidat)
- [ ] Migration + modèle + factory `Entretien`
- [ ] Migration + modèle + factory `EvaluationCandidat`
- [ ] Migration + modèle `CandidatureHistorique` (traçabilité)
- [ ] Enums : `StatutOffre`, `StatutCandidature` (`recue`, `preselectionnee`, `en_entretien`, `en_test`, `retenue`, `refusee`), `TypeEtape`
- [ ] `RecrutementSeeder`

### Logique métier
- [ ] `Services/Recrutement/OffreService` : créer une offre **uniquement** à partir d'une demande au statut `recrutement_ouvert`
- [ ] Publier / clôturer une offre
- [ ] `CandidatureService` : réception d'une candidature, recherche d'un `Candidat` existant par email (un même candidat peut postuler plusieurs fois, **ne pas le dupliquer**)
- [ ] Présélection : sélection de dossier, planification d'entretiens et de tests
- [ ] Enregistrer les évaluations (notes + commentaires) et la décision finale (`retenue` / `refusee`)
- [ ] Observer : chaque changement de statut de candidature écrit une ligne dans `recrutement_candidature_historiques`
- [ ] Policies : qui peut évaluer, qui peut décider

### Écrans Filament (`Filament/Resources/Recrutement`)
- [ ] Resource `OffreEmploi` (avec création depuis une demande validée)
- [ ] Resource `Candidat`
- [ ] Resource `Candidature` (vue détail avec entretiens, évaluations et historique)
- [ ] Planification d'un `Entretien` depuis une candidature

### Tests
- [ ] Test : impossible de créer une offre depuis une demande non validée
- [ ] Test : un candidat avec deux candidatures reste un seul `Candidat`

---

## 5. Bloc 3 — Sélection et embauche (Lionel) · branche `bloc3-embauche`

**Rappel de conception : Candidat ≠ Employé.**
**Dépend de :** `recrutement_candidatures` (Bloc 2). À commencer après avoir fusionné la base du Bloc 2.

### Données
- [ ] Migration + modèle + factory `PropositionEmbauche` (salaire, horaires, lieu de travail, date de début)
- [ ] Migration + modèle + factory `Employe` (matricule nullable, **sans** `type_contrat`)
- [ ] Enum `StatutProposition`
- [ ] `EmbaucheSeeder`

### Logique métier
- [ ] `Services/Embauche/PropositionService` : créer une proposition pour une candidature `retenue`
- [ ] Acceptation / refus par le candidat (enregistrer la date de réponse)
- [ ] À l'acceptation : créer l'`Employe` à partir du `Candidat` (sans matricule, voir « Décisions à confirmer »)
- [ ] À l'acceptation : déclencher l'événement `PropositionAcceptee` (Voara crée le contrat et l'affectation)
- [ ] `GenererContratPdf` : génération **automatique** du PDF du contrat après acceptation (salaire, horaires, lieu de travail, dates). Paquet suggéré : `barryvdh/laravel-dompdf` — **prévenir Samuel** pour l'ajout dans `composer.json`
- [ ] Policy : qui peut faire une proposition

### Écrans Filament (`Filament/Resources/Embauche`)
- [ ] Resource `PropositionEmbauche` (boutons Envoyer / Accepter / Refuser)
- [ ] Resource `Employe` (fiche salarié : informations + liens vers contrats et affectations quand le Bloc 4 sera fusionné)

### Tests
- [ ] Test : l'acceptation crée un `Employe` lié au bon `Candidat`
- [ ] Test : impossible de faire une proposition pour une candidature non retenue

---

## 6. Bloc 4 — Contrat et onboarding (Voara) · branche `bloc4-contrat`

**Rappel de conception : ne pas écraser l'historique.** Un salarié a plusieurs contrats successifs (CDD, renouvellement, CDI…), jamais un simple champ `employe.type_contrat`.
**Dépend de :** `embauche_employes` et `embauche_proposition_embauches` (Bloc 3), `besoin_postes` (Bloc 1). Voara peut commencer par les migrations qui ne dépendent que de `embauche_employes` dès que Lionel l'a poussée, et les autres ensuite.

### Données
- [ ] Migration + modèle + factory `Contrat` (statut, `contrat_precedent_id`)
- [ ] Migration + modèle + factory `Affectation`
- [ ] Migration + modèle + factory `Onboarding`
- [ ] Migration + modèle + factory `OnboardingTache`
- [ ] Migration + modèle `Document` (polymorphe)
- [ ] Enums : `TypeContrat`, `StatutContrat` (`cree`, `signe`, `valide`), `StatutTache`
- [ ] Relations : un `Employe` a plusieurs `Contrat` et plusieurs `Affectation` (à ajouter dans `Employe` : demander à Lionel ou le faire dans un petit commit isolé)
- [ ] `ContratSeeder`

### Logique métier
- [ ] Listener de `PropositionAcceptee` : création du `Contrat` (statut `cree`) et de l'`Affectation`
- [ ] `Services/Contrat/ContratService` : passage `cree` → `signe` → `valide`
- [ ] **Au passage à `signe` : génération du matricule** (unique, ex. `EMP-2026-0001`)
- [ ] Renouvellement : créer un nouveau contrat lié à `contrat_precedent_id`, l'ancien reste intact
- [ ] `Services/Contrat/OnboardingService` : à la validation du contrat, créer l'onboarding et sa **checklist** : contrat signé, badge, compte, matériel, formation, inscription paie
- [ ] Une tâche d'onboarding a un responsable et une échéance ; l'onboarding est « terminé » quand toutes les tâches le sont
- [ ] Gestion des `Document` (upload d'un contrat signé, pièces d'identité…) sur un employé ou un contrat
- [ ] Observer : audit des changements de statut du contrat

### Écrans Filament (`Filament/Resources/Contrat`)
- [ ] Resource `Contrat` (historique par employé, actions Signer / Valider / Renouveler)
- [ ] Resource `Affectation`
- [ ] Resource `Onboarding` avec checklist cochable
- [ ] Gestion des documents

### Tests
- [ ] Test : le matricule n'existe qu'après la signature du contrat
- [ ] Test : un renouvellement conserve l'ancien contrat
- [ ] Test : l'onboarding est créé avec toutes ses tâches

---

## 7. Intégration (tous les trois, à la fin des bases)

- [ ] Parcours complet de bout en bout : demande → validations → offre → candidatures → entretiens → proposition → acceptation → contrat → signature (matricule) → onboarding
- [ ] `php artisan migrate:fresh --seed` fonctionne sans erreur sur une base vide
- [ ] Un clone vierge + `./install.sh` + `./lancer.sh` fonctionne chez chacun
- [ ] Revue croisée : chacun relit un bloc d'un autre
- [ ] Mettre à jour le README et `docs/` (schéma de la base, workflow)