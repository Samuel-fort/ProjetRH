#!/usr/bin/env bash
# =====================================================================
# Prérequis projet SIRH (Laravel + PostgreSQL) - Ubuntu
# Vérifie chaque outil et installe uniquement ce qui manque.
# Usage :  chmod +x setup_prerequis.sh && ./setup_prerequis.sh
# Ne PAS lancer avec sudo : le script demande sudo quand il en a besoin.
# =====================================================================

set -u

PHP_MIN="8.2"          # version minimale exigée par Laravel récent
DB_NAME="rh_sirh"
DB_USER="rh_user"
NVM_VERSION="v0.40.1"

# ---------- Affichage ----------
OK="\e[32m[OK]\e[0m"
KO="\e[31m[MANQUANT]\e[0m"
INFO="\e[34m[INFO]\e[0m"
WARN="\e[33m[ATTENTION]\e[0m"
ERR="\e[31m[ERREUR]\e[0m"

RESULTS=()
add_result() { RESULTS+=("$1|$2"); }

has() { command -v "$1" >/dev/null 2>&1; }

version_ge() {  # version_ge 8.3 8.2  => vrai si 8.3 >= 8.2
  [ "$(printf '%s\n%s\n' "$2" "$1" | sort -V | head -n1)" = "$2" ]
}

# ---------- Contrôles préalables ----------
if [ "$(id -u)" -eq 0 ]; then
  echo -e "$ERR Ne lance pas ce script avec sudo ou en root."
  echo "      Lance-le avec ton utilisateur normal : ./setup_prerequis.sh"
  exit 1
fi

if ! grep -qi "ubuntu" /etc/os-release 2>/dev/null; then
  echo -e "$WARN Ce script est prévu pour Ubuntu. Ton système est différent."
  read -rp "Continuer quand même ? (o/N) " rep
  [[ "$rep" =~ ^[oOyY]$ ]] || exit 1
fi

if ! has sudo; then
  echo -e "$ERR sudo est introuvable. Installe-le ou lance en tant qu'administrateur."
  exit 1
fi

echo -e "$INFO Vérification de la connexion Internet..."
if ! ping -c1 -W3 archive.ubuntu.com >/dev/null 2>&1 && ! curl -sI --max-time 5 https://github.com >/dev/null 2>&1; then
  echo -e "$WARN Pas d'accès Internet détecté. Les installations vont échouer si quelque chose manque."
fi

APT_UPDATED=0
apt_update_once() {
  if [ "$APT_UPDATED" -eq 0 ]; then
    echo -e "$INFO apt update..."
    sudo apt-get update -y
    APT_UPDATED=1
  fi
}
apt_install() {
  apt_update_once
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y "$@"
}

# =====================================================================
# 1. Outils de base : git, curl, unzip
# =====================================================================
echo; echo "=== 1. Outils de base ==="
for tool in git curl unzip; do
  if has "$tool"; then
    echo -e "$OK $tool"
  else
    echo -e "$KO $tool -> installation"
    apt_install "$tool"
  fi
done
for tool in git curl unzip; do
  if has "$tool"; then add_result "$tool" "OK"; else add_result "$tool" "ECHEC"; fi
done

# =====================================================================
# 2. PHP + extensions
# =====================================================================
echo; echo "=== 2. PHP ($PHP_MIN ou plus) et extensions ==="

php_version() { php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null; }

PHP_OK=0
if has php; then
  CUR="$(php_version)"
  if version_ge "$CUR" "$PHP_MIN"; then
    echo -e "$OK PHP $CUR"
    PHP_OK=1
  else
    echo -e "$KO PHP $CUR trop ancien (minimum $PHP_MIN)"
  fi
else
  echo -e "$KO PHP absent"
fi

if [ "$PHP_OK" -eq 0 ]; then
  apt_install php php-cli
  CUR="$(php_version)"
  if [ -z "$CUR" ] || ! version_ge "$CUR" "$PHP_MIN"; then
    echo -e "$WARN La version PHP d'Ubuntu est insuffisante. Ajout du dépôt ondrej/php (PHP 8.3)..."
    apt_install software-properties-common
    sudo add-apt-repository -y ppa:ondrej/php
    APT_UPDATED=0
    apt_install php8.3 php8.3-cli
    sudo update-alternatives --set php /usr/bin/php8.3 >/dev/null 2>&1 || true
  fi
fi

# Extensions requises (nom du module PHP -> suffixe du paquet apt)
declare -A EXT=(
  [mbstring]=mbstring [xml]=xml [curl]=curl [zip]=zip
  [intl]=intl [bcmath]=bcmath [pdo_pgsql]=pgsql [pgsql]=pgsql
)
PHPV="$(php_version)"
MISSING_PKGS=()
for mod in "${!EXT[@]}"; do
  if php -m 2>/dev/null | grep -qix "$mod"; then
    echo -e "$OK extension php-$mod"
  else
    echo -e "$KO extension php-$mod"
    MISSING_PKGS+=("php${PHPV}-${EXT[$mod]}")
  fi
done
if [ "${#MISSING_PKGS[@]}" -gt 0 ]; then
  # dédoublonnage
  mapfile -t UNIQ < <(printf '%s\n' "${MISSING_PKGS[@]}" | sort -u)
  echo -e "$INFO Installation : ${UNIQ[*]}"
  apt_install "${UNIQ[@]}" || {
    echo -e "$WARN Échec avec la version précise, essai avec les noms génériques..."
    GEN=()
    for p in "${UNIQ[@]}"; do GEN+=("php-${p#php${PHPV}-}"); done
    apt_install "${GEN[@]}"
  }
fi

if has php && version_ge "$(php_version)" "$PHP_MIN"; then
  add_result "PHP $(php_version)" "OK"
else
  add_result "PHP" "ECHEC"
fi
if php -m 2>/dev/null | grep -qix "pdo_pgsql"; then
  add_result "PHP pdo_pgsql" "OK"
else
  add_result "PHP pdo_pgsql" "ECHEC"
fi

# =====================================================================
# 3. Composer
# =====================================================================
echo; echo "=== 3. Composer ==="
if has composer; then
  echo -e "$OK $(composer --version 2>/dev/null | head -n1)"
else
  echo -e "$KO Composer -> installation"
  apt_install composer
fi
if has composer; then add_result "Composer" "OK"; else add_result "Composer" "ECHEC"; fi

# =====================================================================
# 4. PostgreSQL
# =====================================================================
echo; echo "=== 4. PostgreSQL ==="
if has psql; then
  echo -e "$OK $(psql --version)"
else
  echo -e "$KO PostgreSQL -> installation"
  apt_install postgresql postgresql-contrib
fi

if has psql; then
  # Démarrage du service si nécessaire
  if ! sudo systemctl is-active --quiet postgresql 2>/dev/null; then
    echo -e "$INFO Démarrage du service PostgreSQL..."
    sudo systemctl enable --now postgresql 2>/dev/null || sudo service postgresql start
  fi
  if sudo systemctl is-active --quiet postgresql 2>/dev/null || sudo -u postgres psql -c "select 1" >/dev/null 2>&1; then
    echo -e "$OK Service PostgreSQL actif"
    add_result "PostgreSQL" "OK"
  else
    add_result "PostgreSQL (service)" "ECHEC"
  fi
else
  add_result "PostgreSQL" "ECHEC"
fi

# =====================================================================
# 5. Node.js (via nvm)
# =====================================================================
echo; echo "=== 5. Node.js ==="
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"

if has node && has npm; then
  echo -e "$OK node $(node -v) / npm $(npm -v)"
else
  echo -e "$KO Node.js -> installation via nvm"
  if [ ! -s "$NVM_DIR/nvm.sh" ]; then
    curl -fsSL "https://raw.githubusercontent.com/nvm-sh/nvm/${NVM_VERSION}/install.sh" | bash
    [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
  fi
  if command -v nvm >/dev/null 2>&1; then
    nvm install --lts
    nvm use --lts >/dev/null
  else
    echo -e "$ERR nvm n'a pas pu être chargé."
  fi
fi
if has node && has npm; then
  add_result "Node.js $(node -v)" "OK"
else
  add_result "Node.js" "ECHEC"
fi

# =====================================================================
# 6. Configuration Git
# =====================================================================
echo; echo "=== 6. Configuration Git ==="
GN="$(git config --global user.name 2>/dev/null || true)"
GE="$(git config --global user.email 2>/dev/null || true)"
if [ -n "$GN" ] && [ -n "$GE" ]; then
  echo -e "$OK Git configuré : $GN <$GE>"
else
  echo -e "$KO Identité Git non configurée"
  read -rp "Ton nom complet : " NAME
  read -rp "Ton email : " MAIL
  [ -n "$NAME" ] && git config --global user.name "$NAME"
  [ -n "$MAIL" ] && git config --global user.email "$MAIL"
fi
if [ -n "$(git config --global user.name)" ] && [ -n "$(git config --global user.email)" ]; then
  add_result "Identité Git" "OK"
else
  add_result "Identité Git" "ECHEC"
fi

# Clé SSH
if ls "$HOME"/.ssh/id_*.pub >/dev/null 2>&1; then
  echo -e "$OK Clé SSH déjà présente"
  add_result "Clé SSH" "OK"
else
  echo -e "$KO Aucune clé SSH"
  read -rp "Générer une clé SSH pour GitHub maintenant ? (O/n) " rep
  if [[ ! "$rep" =~ ^[nN]$ ]]; then
    mkdir -p "$HOME/.ssh" && chmod 700 "$HOME/.ssh"
    ssh-keygen -t ed25519 -C "$(git config --global user.email)" -f "$HOME/.ssh/id_ed25519" -N ""
    echo -e "$INFO Copie cette clé publique dans GitHub (Settings > SSH and GPG keys) :"
    echo "------------------------------------------------------------"
    cat "$HOME/.ssh/id_ed25519.pub"
    echo "------------------------------------------------------------"
    add_result "Clé SSH" "OK"
  else
    add_result "Clé SSH" "IGNORE"
  fi
fi

# =====================================================================
# 7. Base de données du projet
# =====================================================================
echo; echo "=== 7. Base de données locale ($DB_NAME) ==="
if has psql && sudo -u postgres psql -c "select 1" >/dev/null 2>&1; then
  DB_EXISTS="$(sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='${DB_NAME}'" 2>/dev/null)"
  USER_EXISTS="$(sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='${DB_USER}'" 2>/dev/null)"

  if [ "$DB_EXISTS" = "1" ] && [ "$USER_EXISTS" = "1" ]; then
    echo -e "$OK Base '$DB_NAME' et utilisateur '$DB_USER' déjà présents"
    add_result "Base $DB_NAME" "OK"
  else
    echo -e "$KO Base ou utilisateur manquant"
    read -rp "Créer la base '$DB_NAME' et l'utilisateur '$DB_USER' ? (O/n) " rep
    if [[ ! "$rep" =~ ^[nN]$ ]]; then
      while true; do
        read -rsp "Choisis un mot de passe pour '$DB_USER' : " DB_PASS; echo
        read -rsp "Confirme le mot de passe : " DB_PASS2; echo
        [ -n "$DB_PASS" ] && [ "$DB_PASS" = "$DB_PASS2" ] && break
        echo -e "$WARN Les mots de passe sont vides ou différents, recommence."
      done
      ESC_PASS="${DB_PASS//\'/\'\'}"
      if [ "$USER_EXISTS" != "1" ]; then
        sudo -u postgres psql -c "CREATE USER ${DB_USER} WITH PASSWORD '${ESC_PASS}';" >/dev/null
      else
        sudo -u postgres psql -c "ALTER USER ${DB_USER} WITH PASSWORD '${ESC_PASS}';" >/dev/null
      fi
      if [ "$DB_EXISTS" != "1" ]; then
        sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};" >/dev/null
      fi
      # Vérification réelle de la connexion
      if PGPASSWORD="$DB_PASS" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -c "select 1" >/dev/null 2>&1; then
        echo -e "$OK Connexion à '$DB_NAME' réussie"
        add_result "Base $DB_NAME" "OK"
      else
        echo -e "$WARN La base est créée mais la connexion TCP a échoué (vérifie pg_hba.conf)."
        add_result "Base $DB_NAME" "A VERIFIER"
      fi
      unset DB_PASS DB_PASS2 ESC_PASS
    else
      add_result "Base $DB_NAME" "IGNORE"
    fi
  fi
else
  echo -e "$WARN PostgreSQL n'est pas utilisable, base non créée."
  add_result "Base $DB_NAME" "ECHEC"
fi

# =====================================================================
# Récapitulatif
# =====================================================================
echo
echo "================ RÉCAPITULATIF ================"
FAIL=0
for r in "${RESULTS[@]}"; do
  name="${r%%|*}"; st="${r##*|}"
  case "$st" in
    OK)          printf "  %-28s \e[32m%s\e[0m\n" "$name" "OK" ;;
    IGNORE)      printf "  %-28s \e[33m%s\e[0m\n" "$name" "ignoré" ;;
    "A VERIFIER") printf "  %-28s \e[33m%s\e[0m\n" "$name" "à vérifier" ; ;;
    *)           printf "  %-28s \e[31m%s\e[0m\n" "$name" "ECHEC"; FAIL=1 ;;
  esac
done
echo "==============================================="

if [ "$FAIL" -eq 0 ]; then
  echo -e "$OK Tout est prêt."
  if ! command -v nvm >/dev/null 2>&1 || [ ! -s "$NVM_DIR/nvm.sh" ]; then :; fi
  echo -e "$INFO Si Node vient d'être installé, ouvre un NOUVEAU terminal pour qu'il soit reconnu."
else
  echo -e "$ERR Certains éléments ont échoué : relis les messages ci-dessus."
  exit 1
fi