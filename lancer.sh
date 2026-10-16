#!/usr/bin/env bash
# =====================================================================
# Lance le projet SIRH en local
#   ./lancer.sh            -> serveur Laravel (+ Vite si disponible)
#   PORT=9000 ./lancer.sh  -> autre port
# Arret : Ctrl+C
# =====================================================================
set -uo pipefail
cd "$(dirname "$(readlink -f "$0")")"

PORT="${PORT:-8000}"
OK="\e[32m[OK]\e[0m"; INFO="\e[34m[INFO]\e[0m"; ERR="\e[31m[ERREUR]\e[0m"

export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"

[ -f artisan ] || { echo -e "$ERR artisan introuvable : lance d'abord ./install.sh"; exit 1; }
[ -s .env ]    || { echo -e "$ERR .env vide : lance d'abord ./install.sh"; exit 1; }

# PostgreSQL doit tourner
if ! sudo systemctl is-active --quiet postgresql 2>/dev/null; then
  echo -e "$INFO Demarrage de PostgreSQL..."
  sudo systemctl start postgresql 2>/dev/null || sudo service postgresql start
fi

# Migrations en attente (silencieux si rien a faire)
php artisan migrate --force --no-interaction >/dev/null 2>&1 || echo -e "$ERR Migrations en echec (verifie .env / PostgreSQL)"

PIDS=()
cleanup() { echo; echo -e "$INFO Arret..."; for p in "${PIDS[@]}"; do kill "$p" 2>/dev/null; done; exit 0; }
trap cleanup INT TERM

# Vite (rechargement automatique du front) si disponible
if [ -f package.json ] && grep -q '"dev"' package.json && [ -d node_modules ] && command -v npm >/dev/null 2>&1; then
  npm run dev >/dev/null 2>&1 &
  PIDS+=($!)
  echo -e "$OK Vite lance"
fi

echo -e "$OK Application : http://127.0.0.1:${PORT}"
echo -e "$OK Admin       : http://127.0.0.1:${PORT}/admin"
php artisan serve --host=127.0.0.1 --port="$PORT"
cleanup
