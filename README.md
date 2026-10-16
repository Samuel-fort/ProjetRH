# ProjetRH

Application de gestion des ressources humaines (SIRH) construite avec Laravel. Le projet a vocation a centraliser les besoins RH, les recrutements, les embauches et les contrats au sein d'un panneau d'administration Filament.

## Etat du projet

Le socle Laravel et les scripts d'installation sont en place. La route publique `/` utilise encore la vue d'accueil Laravel et `routes/api.php` ne contient pas encore d'endpoint. Le script d'installation prépare toutefois l'environnement PostgreSQL et installe Filament pour le panneau d'administration.

## Prerequis

- Ubuntu ou une distribution Linux compatible avec les scripts fournis
- PHP 8.3 ou superieur avec les extensions `mbstring`, `xml`, `curl`, `zip`, `intl`, `bcmath`, `pgsql` et `pdo_pgsql`
- Composer
- PostgreSQL
- Node.js et npm
- Git

Le script `script_lancement/prerequis.sh` peut installer et vérifier les dépendances manquantes sur Ubuntu. Il ne doit pas être exécuté avec `sudo`.

## Installation

Depuis la racine du dépôt :

```bash
cd script_lancement
chmod +x prerequis.sh install.sh lancer.sh
./prerequis.sh
```

Après l'installation éventuelle des outils, ouvrez un nouveau terminal puis lancez :

```bash
cd script_lancement
./install.sh
```

Le script d'installation :

1. installe les dépendances PHP ;
2. crée et configure `.env` ;
3. configure la connexion PostgreSQL à la base `rh_sirh` avec l'utilisateur `rh_user` ;
4. exécute les migrations ;
5. installe Filament et propose de créer un compte administrateur ;
6. installe les dépendances JavaScript et compile les assets.

Ne lancez pas `install.sh` avec `sudo`. Le mot de passe PostgreSQL est demandé interactivement pendant l'installation.

## Lancement

Depuis `script_lancement` :

```bash
./lancer.sh
```

L'application est alors disponible à l'adresse <http://127.0.0.1:8000>. Le panneau d'administration Filament est accessible à <http://127.0.0.1:8000/admin> lorsque Filament a été installé et qu'un compte administrateur a été créé.

Pour utiliser un autre port :

```bash
PORT=9000 ./lancer.sh
```

Le script démarre PostgreSQL si nécessaire, applique les migrations en attente et lance Vite pour le rechargement des assets lorsque les dépendances JavaScript sont présentes. Arrêtez les serveurs avec `Ctrl+C`.

## Commandes utiles

Les commandes suivantes s'exécutent depuis la racine du projet :

```bash
# Installer les dépendances PHP
composer install

# Installer les dépendances front-end
npm install

# Compiler les assets pour la production
npm run build

# Lancer Vite en mode développement
npm run dev

# Exécuter les tests
php artisan test
```

Le script Composer `setup` automatise une installation Laravel standard avec SQLite. Pour l'installation prévue par ce dépôt, utilisez plutôt `script_lancement/install.sh`, qui configure PostgreSQL et Filament.

## Structure du projet

```text
app/
├── Enums/             Enums métier
├── Filament/           Ressources et configuration Filament
├── Http/               Contrôleurs et requêtes HTTP
├── Models/             Modèles Eloquent
├── Notifications/      Notifications applicatives
├── Observers/          Observateurs Eloquent
├── Policies/           Autorisations
├── Providers/          Fournisseurs de services
└── Services/           Services métier
database/               Migrations, factories et seeders
resources/              Vues Blade, JavaScript et CSS
routes/                 Routes web, API et console
script_lancement/       Scripts de prérequis, installation et démarrage
tests/                  Tests unitaires et fonctionnels
```

## Configuration

La configuration locale se trouve dans `.env`, qui ne doit pas être versionné. Le fichier `.env.example` sert de modèle. Les paramètres PostgreSQL utilisés par les scripts sont :

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rh_sirh
DB_USERNAME=rh_user
```

La valeur de `DB_PASSWORD` est renseignée par `install.sh` et ne doit pas être ajoutée au dépôt.

## Tests

Les tests utilisent SQLite en mémoire lorsqu'ils sont lancés via PHPUnit, conformément à `phpunit.xml` :

```bash
php artisan test
```

## Dépannage

- `artisan introuvable` : exécutez les commandes depuis `script_lancement` ou revenez à la racine du projet selon la commande utilisée.
- Échec de connexion PostgreSQL : vérifiez que le service PostgreSQL est démarré et que `rh_sirh` et `rh_user` existent.
- Assets absents : exécutez `npm install`, puis `npm run build`.
- Configuration Laravel obsolète après une modification de `.env` : exécutez `php artisan config:clear`.