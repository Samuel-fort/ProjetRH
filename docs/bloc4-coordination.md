# Coordination du Bloc 4

## 1. Hypothèses sur les autres blocs

- Cas B du protocole : le Bloc 3 n'était pas présent lors de la création du Bloc 4; son stub `App\Models\Embauche\Employe` et la migration correspondante sont maintenant présents dans le dépôt.
- Les intégrations restent à confirmer avec le Bloc 3 : l'événement est supposé être `App\Events\PropositionAcceptee`, avec les propriétés publiques `proposition` (`PropositionEmbauche`) et `employe` (`Employe`).
- `PropositionEmbauche` est supposée exposer `salaire`, `horaires`, `lieu_travail`, `date_debut`, puis les relations `candidature()`, `offreEmploi()` et `demandeRecrutement()` donnant accès à `poste_id` et `departement_id`.
- Le service PDF est supposé être `App\Services\Embauche\GenererContratPdf`, avec une méthode `generer(Contrat $contrat)` retournant un chemin, un `Document` ou `null`.
- Aucune de ces trois interfaces d'intégration n'existe dans le dépôt à ce jour ; aucun stub n'est créé pour elles.
- La vérification `php artisan event:list` réussit sans l'événement du Bloc 3 ; Laravel affiche le nom d'événement inféré depuis le type du listener.
- La notification `TacheOnboardingAssignee` est implémentée dans le Bloc 4 et envoyée par `OnboardingService::assignerTache()` lorsqu'un responsable est défini.

## 2. Éléments temporaires à supprimer

Les trois fichiers suivants sont des stubs temporaires du Bloc 3, créés selon le cas B. Le responsable du Bloc 3 devra les remplacer par ses implémentations puis les retirer de ce lot après vérification de la propriété des migrations et des données existantes :

- `database/migrations/2026_10_16_399999_creer_stub_embauche_employes_table.php`
- `app/Models/Embauche/Employe.php`
- `database/factories/Embauche/EmployeFactory.php`

La migration temporaire ne supprime pas la table dans son `down()` afin de ne pas supprimer une table ensuite gérée par le Bloc 3. Avant son retrait, le responsable du Bloc 3 doit vérifier que sa migration de production prend en charge la table existante sans perte de données.

Les policies utilisent désormais les valeurs de `RoleUtilisateur`; les rôles absents, nuls ou non autorisés sont refusés.

Le raccordement de `PropositionAcceptee` et de `GenererContratPdf` est temporairement fondé sur les interfaces décrites à la section 1. Après leur livraison par le Bloc 3, le responsable du Bloc 4 doit vérifier les noms de classes, propriétés, relations et type de retour, adapter uniquement `DonneesContratDepuisProposition` si les données diffèrent, puis activer les tests d'intégration actuellement ignorés.

## 3. Demandes aux responsables des fichiers partagés

- **Au responsable du Bloc 3 — modèle salarié :** intégrer le trait `PossedeContrats` dans `App\Models\Embauche\Employe` et remplacer le modèle/factory stub; cela rend les relations du Bloc 4 disponibles sans dupliquer le modèle partagé.
- **Au responsable de la fiche salarié du Bloc 3 :** ajouter sur la fiche `Employe` des liens ou gestionnaires de relations vers les contrats et les affectations; les ressources Filament correspondantes sont déjà découvrables.
- **Au responsable des fichiers partagés — panneau :** confirmer si `canAccessPanel` doit appliquer les rôles existants afin de contrôler l'accès global au panneau; les policies du Bloc 4 limitent déjà les ressources et leurs actions.
- **Au responsable du Bloc 3 — événement et PDF :** fournir `App\Events\PropositionAcceptee` avec `proposition` et `employe`, et confirmer la signature ainsi que le résultat de `GenererContratPdf::generer()`; ces interfaces déclenchent le contrat et son document PDF.
- **Au responsable du Bloc 3 — clé de proposition :** lorsque la table réelle de `PropositionEmbauche` est disponible, ajouter une migration ultérieure pour contraindre `contrat_contrats.proposition_embauche_id`; la migration livrée reste indépendante du schéma non fusionné.
- **Au responsable du Bloc 3 — intégration :** confirmer les noms, propriétés, relations et types de retour de `PropositionAcceptee`, `PropositionEmbauche` et `GenererContratPdf` décrits à la section 1 afin de valider le point d'adaptation et réactiver les tests correspondants.

## 4. Décisions prises

- Un contrat créé à partir d'une proposition est de type `cdi` par défaut : la proposition du Bloc 3 ne porte ni type ni date de fin, et ces champs restent modifiables au statut `cree`.
- `proposition_embauche_id` reste nullable, unique et sans clé étrangère dans la première migration afin de préserver l'idempotence sans créer de dépendance vers une table qui n'est pas encore intégrée.
- La migration temporaire de l'employé vérifie l'existence de la table avant sa création et ne la supprime pas lors du rollback afin de protéger une table éventuellement déjà adoptée par le Bloc 3.
- Si plusieurs contrats valides ont la même `date_debut`, le plus grand identifiant sert de départage pour obtenir un renouvellement source déterministe.
- Un onboarding sans tâche n'est pas considéré comme terminé afin d'éviter qu'une checklist vide satisfasse artificiellement la condition de fin.
- L'historique des statuts est écrit explicitement par le seeder, car `DatabaseSeeder` désactive les événements Eloquent.
- La notification d'assignation utilise uniquement le canal mail; le canal database est exclu car aucune migration dédiée n'existe dans le périmètre du Bloc 4.
- `DatabaseSeeder` appelle actuellement `UtilisateurSeeder`, `BesoinSeeder`, `RecrutementSeeder`, `EmbaucheSeeder` puis `ContratSeeder`; cet ordre fournit au seeder les rôles, postes et départements déjà préparés. `DatabaseSeeder` désactive les événements Eloquent, et `ContratSeeder` maintient le même comportement lorsqu'il est exécuté seul.

## 5. Points ouverts

- Faire valider par les responsables du Bloc 3 les hypothèses d'interface et le point d'adaptation avant de raccorder l'événement.
- Remplacer les trois stubs temporaires par les modèles/migrations réels du Bloc 3 après vérification de l'ordre des migrations et de la conservation des données.
- Ajouter la clé étrangère de `proposition_embauche_id` dans une migration dédiée lorsque la table cible réelle sera disponible.
- Confirmer la disponibilité, la signature et le résultat du générateur PDF; activer ensuite le test d'intégration PDF ignoré.
- Confirmer avec le responsable des fichiers partagés si `canAccessPanel` doit restreindre l'accès global au panneau selon les rôles; les policies des ressources appliquent déjà leurs contrôles propres.
- Valider le parcours de bout en bout après intégration des Blocs 1, 2 et 3, y compris les relations salarié, la proposition acceptée, le document PDF et l'accès selon rôle.

## Procédure d'intégration du Bloc 3

1. **Réinitialiser la base de développement après retrait des stubs.** La table `embauche_employes` créée par la migration temporaire existe déjà dans la base locale; la migration réelle du Bloc 3 échouera avec une erreur indiquant que la table existe si elle est appliquée telle quelle. Après remplacement des stubs, le responsable de l'intégration doit réinitialiser la base locale à partir de l'ensemble des migrations et seeders fusionnés. Ne pas tenter de contourner ce conflit en modifiant la migration réelle du Bloc 3.
2. **Remplacer les trois fichiers temporaires**, en conservant les fichiers équivalents du Bloc 3 en cas de conflit :
   - `database/migrations/2026_10_16_399999_creer_stub_embauche_employes_table.php`
   - `app/Models/Embauche/Employe.php`
   - `database/factories/Embauche/EmployeFactory.php`
3. **Ajouter la relation au modèle réel.** Dans `App\Models\Embauche\Employe`, importer `App\Models\Contrat\Concerns\PossedeContrats`, puis utiliser le trait dans la classe :
   ```php
   use App\Models\Contrat\Concerns\PossedeContrats;

   class Employe extends Model
   {
       use PossedeContrats;
   }
   ```
4. **Valider les interfaces d'intégration.** Confirmer que `App\Events\PropositionAcceptee` expose `public PropositionEmbauche $proposition` et `public Employe $employe`; que `PropositionEmbauche` expose `salaire`, `horaires`, `lieu_travail`, `date_debut` et le chemin relationnel `candidature()->offreEmploi()->demandeRecrutement()` donnant les identifiants du poste et du département; et que `App\Services\Embauche\GenererContratPdf::generer(Contrat $contrat)` retourne un chemin (`string`), un `Document` ou `null`. Les lectures/adaptations de la proposition doivent rester centralisées dans `DonneesContratDepuisProposition`.
5. **Réactiver les tests d'intégration ignorés après livraison de ces interfaces.**
   - `tests/Feature/Contrat/ListenerContratTest.php::test_acceptation_cree_un_contrat_et_une_affectation_de_maniere_idempotente` est ignoré tant que `App\Events\PropositionAcceptee` et `App\Models\Embauche\PropositionEmbauche` ne sont pas disponibles.
   - `tests/Feature/Contrat/ContratServiceTest.php::test_generateur_pdf_peut_enregistrer_un_document_lorsquil_est_present` est ignoré tant que `App\Services\Embauche\GenererContratPdf` n'est pas disponible; fournir cette classe et sa signature permet de l'exécuter.
   - Le test complémentaire `test_generateur_pdf_absent_ne_cree_aucun_document` s'exécute lorsque le générateur est absent et s'ignore lorsqu'il est présent; il vérifie les deux configurations sans remplacer le test d'intégration.
6. **Vérifier le contrôle des rôles.** `users.role` et `RoleUtilisateur` sont disponibles; `VerifieRolesContrat::aRole()` applique une comparaison stricte et les rôles absents, nuls ou inconnus sont refusés.
7. **Ajouter la clé étrangère de proposition dans une nouvelle migration.** Lorsque la table réelle de `PropositionEmbauche` est connue, créer une migration ultérieure qui ajoute la contrainte sur `contrat_contrats.proposition_embauche_id`; ne pas modifier la migration du Bloc 4 déjà livrée.
8. **Seeder partagé déjà raccordé.** `DatabaseSeeder` appelle `ContratSeeder::class` après les seeders des Blocs 1 à 3. Le test `DatabaseSeederContratTest` valide la chaîne complète; le seeder exécute son corps dans `Model::withoutEvents()` et écrit une entrée explicite par transition.

## Résidus de Laravel Boost

Inventaire effectué pour le Prompt 06 :

- `composer.json` : aucune occurrence de `laravel/boost` dans `require` ou `require-dev`.
- `composer.lock` : aucune occurrence de `laravel/boost`.
- À la racine, `.claude/`, `.mcp.json` et `boost.json` sont absents; aucun fichier n'a été supprimé.
- Le code du Bloc 4 examiné ne contient ni import ni appel à Laravel Boost.
- `AGENTS.md` et `CLAUDE.md` existent, mais leur contenu n'a pas été inspecté car les règles du document de contexte interdisent explicitement de les lire. Il n'est donc pas possible de confirmer ou d'infirmer la présence de marqueurs Boost dans ces fichiers.
- Les manifests Composer ne référencent actuellement pas Boost. Comme leur historique ne peut pas être consulté selon les règles du projet, leur état antérieur ne peut pas être établi à partir de cet inventaire.

L'utilisateur doit inspecter lui-même `AGENTS.md` et `CLAUDE.md` pour tout bloc Boost et les nettoyer ou restaurer si nécessaire; il peut également comparer/restaurer `composer.json` et `composer.lock` à la version attendue s'il sait que l'installation Boost y a apporté d'autres changements. Aucun des quatre fichiers n'a été modifié pendant le Prompt 06.
