#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$(readlink -f "$0")")"

OK="\e[32m[OK]\e[0m"
INFO="\e[34m[INFO]\e[0m"
ERR="\e[31m[ERREUR]\e[0m"

command -v php >/dev/null 2>&1 || {
  echo -e "$ERR PHP est introuvable."
  exit 1
}

[ -f artisan ] || {
  echo -e "$ERR artisan est introuvable."
  exit 1
}

[ -f vendor/autoload.php ] || {
  echo -e "$ERR vendor/ est absent. Installe les dependances une fois avec Composer avant le mode hors ligne."
  exit 1
}

if [ ! -s .env ]; then
  [ -s .env.example ] || {
    echo -e "$ERR .env et .env.example sont absents."
    exit 1
  }

  cp .env.example .env
  php artisan key:generate --no-interaction
  echo -e "$INFO .env cree et cle Laravel generee"
fi

php artisan optimize:clear >/dev/null
php artisan migrate --force --no-interaction

if php artisan db:seed --class=UtilisateurSeeder --force --no-interaction >/dev/null; then
  echo -e "$OK Utilisateurs de demonstration installes"
fi

if [ -d node_modules ] && command -v npm >/dev/null 2>&1; then
  npm run build
  echo -e "$OK Assets compiles depuis node_modules"
else
  echo -e "$INFO Compilation front ignoree : node_modules ou npm absent"
fi

echo -e "$OK Bootstrap hors ligne termine"
echo "Lance ensuite : php artisan serve"