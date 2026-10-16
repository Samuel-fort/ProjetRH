#!/usr/bin/env bash
# =====================================================================
# Installation du projet SIRH (Laravel + PostgreSQL + Filament)
# A lancer APRES prerequis.sh, depuis la racine du projet :
#     chmod +x install.sh && ./install.sh
# Ne PAS lancer avec sudo.
# Etapes : (0) Laravel si absent -> 3. .env + PostgreSQL + migrate
#          -> 4. Filament -> 5. Front (npm)
# =====================================================================
set -euo pipefail

cd "$(dirname "$(readlink -f "$0")")"

DB_NAME="rh_sirh"
DB_USER="rh_user"
DB_HOST="127.0.0.1"
DB_PORT="5432"

OK="\e[32m[OK]\e[0m"; INFO="\e[34m[INFO]\e[0m"; WARN="\e[33m[ATTENTION]\e[0m"; ERR="\e[31m[ERREUR]\e[0m"

[ "$(id -u)" -eq 0 ] && { echo -e "$ERR Ne lance pas ce script avec sudo."; exit 1; }

# Charge nvm si Node a ete installe par prerequis.sh
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"

for tool in php composer node npm psql; do
  command -v "$tool" >/dev/null 2>&1 || { echo -e "$ERR '$tool' introuvable. Lance d'abord ./prerequis.sh (puis ouvre un nouveau terminal)."; exit 1; }
done

# Remplace (ou ajoute) KEY=valeur dans .env, meme si la ligne est commentee
set_env() {
  local key="$1" val="$2" tmp; tmp="$(mktemp)"
  if grep -qE "^[#[:space:]]*${key}=" .env; then
    VAL="$val" awk -v k="$key" '
      BEGIN{done=0}
      $0 ~ "^[# \t]*"k"=" && !done { print k"="ENVIRON["VAL"]; done=1; next }
      { print }' .env > "$tmp"
  else
    cp .env "$tmp"; printf '%s=%s\n' "$key" "$val" >> "$tmp"
  fi
  cat "$tmp" > .env; rm -f "$tmp"
}

# ---------------------------------------------------------------------
# 0. Laravel (uniquement si le projet n'a pas encore ete cree)
# ---------------------------------------------------------------------
if [ ! -f artisan ]; then
  echo; echo "=== 0. Installation de Laravel dans le squelette ==="
  TMP="$(mktemp -d)"
  composer create-project laravel/laravel "$TMP/app" --no-interaction
  rsync -a \
    --exclude .git --exclude README.md --exclude TODOLIST.md \
    --exclude prerequis.sh --exclude install.sh --exclude lancer.sh \
    --exclude structure --exclude br \
    "$TMP/app/" ./
  rm -rf "$TMP"
  echo -e "$OK Laravel installe"
fi

echo; echo "=== Dependances PHP ==="
composer install --no-interaction

# ---------------------------------------------------------------------
# 3. Configuration .env + PostgreSQL
# ---------------------------------------------------------------------
echo; echo "=== 3. Configuration .env et PostgreSQL ==="
if [ ! -s .env ]; then
  [ -s .env.example ] || { echo -e "$ERR .env.example est vide ou absent."; exit 1; }
  cp .env.example .env
  echo -e "$OK .env cree depuis .env.example"
fi

if ! grep -qE '^APP_KEY=.+' .env; then
  php artisan key:generate --no-interaction
fi

while true; do
  read -rsp "Mot de passe de '$DB_USER' (Entree = 'user') : " DB_PASS; echo
  DB_PASS="${DB_PASS:-user}"
  if PGPASSWORD="$DB_PASS" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c "select 1" >/dev/null 2>&1; then
    echo -e "$OK Connexion PostgreSQL reussie"; break
  fi
  echo -e "$WARN Connexion impossible (mauvais mot de passe, ou base/service absent ?)."
  read -rp "Reessayer ? (O/n) " r; [[ "$r" =~ ^[nN]$ ]] && exit 1
done

# Echappement pour une valeur entre guillemets dans .env
ESC="${DB_PASS//\\/\\\\}"; ESC="${ESC//\"/\\\"}"; ESC="${ESC//\$/\\\$}"
set_env DB_CONNECTION pgsql
set_env DB_HOST "$DB_HOST"
set_env DB_PORT "$DB_PORT"
set_env DB_DATABASE "$DB_NAME"
set_env DB_USERNAME "$DB_USER"
set_env DB_PASSWORD "\"$ESC\""
unset DB_PASS ESC
php artisan config:clear >/dev/null
rm -f database/database.sqlite
if ! php artisan db:show 2>/dev/null | grep -qi "pgsql\|postgres"; then
  echo -e "$ERR Laravel n'utilise pas PostgreSQL. Verifie les lignes DB_* de .env puis relance ./install.sh"
  exit 1
fi
echo -e "$OK .env configure pour PostgreSQL"

php artisan migrate --force --no-interaction
echo -e "$OK Migrations executees"

# ---------------------------------------------------------------------
# 4. Filament
# ---------------------------------------------------------------------
echo; echo "=== 4. Filament ==="
if ! grep -q '"filament/filament"' composer.json; then
  composer require filament/filament --no-interaction
else
  echo -e "$OK filament/filament deja present"
fi
if [ ! -f app/Providers/Filament/AdminPanelProvider.php ]; then
  php artisan filament:install --panels --no-interaction
else
  echo -e "$OK Panneau admin deja installe"
fi

read -rp "Creer un compte administrateur Filament maintenant ? (O/n) " r
if [[ ! "$r" =~ ^[nN]$ ]]; then
  php artisan make:filament-user
fi

# ---------------------------------------------------------------------
# 5. Front
# ---------------------------------------------------------------------
echo; echo "=== 5. Front (npm) ==="
if [ -f package.json ]; then
  npm install
  npm run build
  echo -e "$OK Assets compiles"
else
  echo -e "$WARN package.json absent, etape ignoree."
fi

echo; echo -e "$OK Installation terminee. Lance le projet avec :  ./lancer.sh"