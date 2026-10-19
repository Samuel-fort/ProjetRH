#!/usr/bin/env bash
# =====================================================================
# Met a jour une branche de bloc avec main, remet les fichiers au bon
# endroit, retire .env de git, verifie, puis pousse.
#
# Usage (depuis n'importe quel dossier DU projet) :
#     bash maj-branche.sh bloc3-embauche
#     bash maj-branche.sh                  # utilise la branche courante
#
# Le script ne force jamais (pas de push --force) et s'arrete au
# moindre conflit pour te laisser decider.
# =====================================================================
set -uo pipefail

OK="\e[32m[OK]\e[0m"; INFO="\e[34m[INFO]\e[0m"; WARN="\e[33m[ATTENTION]\e[0m"; ERR="\e[31m[ERREUR]\e[0m"
die()  { echo -e "$ERR $*"; exit 1; }
step() { echo; echo -e "\e[1m=== $* ===\e[0m"; }
ask()  { local r; read -rp "$1 (o/N) " r; [[ "$r" =~ ^[oOyY]$ ]]; }

git rev-parse --show-toplevel >/dev/null 2>&1 || die "Lance ce script depuis le dossier du projet (un depot git)."
cd "$(git rev-parse --show-toplevel)"

export NVM_DIR="$HOME/.nvm"; [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"

BRANCH="${1:-$(git rev-parse --abbrev-ref HEAD)}"
[ "$BRANCH" = "main" ] && die "Donne le nom d'une branche de bloc (ex: bloc3-embauche), pas main."
BLOC="$(echo "$BRANCH" | sed -n 's/^bloc\([0-9]\).*/\1/p')"

# ---------------------------------------------------------------------
step "1. Sauvegarde et etat local"
# ---------------------------------------------------------------------
BACKUP=""
if [ -f .env ]; then
  BACKUP="$HOME/.env.sauvegarde.$(date +%Y%m%d-%H%M%S)"
  cp .env "$BACKUP"
  echo -e "$OK .env sauvegarde dans $BACKUP"
fi

# Si .env est suivi par git et modifie, on le remet en etat pour pouvoir changer de branche
# (la sauvegarde ci-dessus sera restauree a la fin)
if git ls-files --error-unmatch .env >/dev/null 2>&1; then
  git checkout -- .env 2>/dev/null || true
fi

DIRTY="$(git status --porcelain | grep -v ' \.env$' || true)"
if [ -n "$DIRTY" ]; then
  echo -e "$WARN Tu as des modifications non commitees :"
  echo "$DIRTY" | head -20
  if ask "Les commiter automatiquement sur la branche courante ($(git rev-parse --abbrev-ref HEAD)) ?"; then
    git add -A -- . ':!.env'
    git commit -m "WIP: sauvegarde avant mise a jour avec main" || true
  else
    die "Commite ou range (git stash) tes modifications, puis relance."
  fi
fi

# ---------------------------------------------------------------------
step "2. Mise a jour de main depuis GitHub"
# ---------------------------------------------------------------------
git fetch origin || die "git fetch a echoue (reseau ? droits ?)."
git checkout main || die "Impossible de passer sur main."
git pull --ff-only origin main || die "main local ne peut pas etre mis a jour proprement."
echo -e "$OK main est a jour"

# ---------------------------------------------------------------------
step "3. Branche $BRANCH"
# ---------------------------------------------------------------------
if git show-ref --verify --quiet "refs/heads/$BRANCH"; then
  git checkout "$BRANCH" || die "Impossible de passer sur $BRANCH."
elif git show-ref --verify --quiet "refs/remotes/origin/$BRANCH"; then
  git checkout -b "$BRANCH" "origin/$BRANCH" || die "Impossible de creer $BRANCH depuis origin."
else
  die "La branche $BRANCH n'existe ni en local ni sur GitHub."
fi

if git show-ref --verify --quiet "refs/remotes/origin/$BRANCH"; then
  AVANCE="$(git rev-list --count "origin/$BRANCH..$BRANCH")"
  RETARD="$(git rev-list --count "$BRANCH..origin/$BRANCH")"
  echo -e "$INFO Commits locaux non pousses : $AVANCE | commits GitHub absents ici : $RETARD"
  if [ "$RETARD" -gt 0 ]; then
    git pull --no-rebase --no-edit origin "$BRANCH" || { echo -e "$ERR Conflit pendant le pull de $BRANCH :"; git diff --name-only --diff-filter=U; echo "Resous les conflits, 'git add' puis 'git commit', et relance le script."; exit 1; }
  fi
fi

# ---------------------------------------------------------------------
step "4. Fusion de main dans $BRANCH"
# ---------------------------------------------------------------------
if ! git merge --no-edit main; then
  echo -e "$ERR Conflits a resoudre dans :"
  git diff --name-only --diff-filter=U
  echo
  echo "Ouvre ces fichiers, garde les deux versions si utiles, supprime les marqueurs <<<<<<< ======= >>>>>>>,"
  echo "puis :  git add -A && git commit  et relance le script."
  echo "Pour tout annuler :  git merge --abort"
  exit 1
fi
echo -e "$OK main fusionne dans $BRANCH"

# ---------------------------------------------------------------------
step "5. Fichiers ranges dans le mauvais dossier (ex: bloc2-donnees/)"
# ---------------------------------------------------------------------
BAD_DIRS="$(git ls-files | cut -d/ -f1 | sort -u | grep -E '^bloc[0-9]+[-_a-zA-Z0-9]*$' || true)"
if [ -z "$BAD_DIRS" ]; then
  echo -e "$OK Aucun dossier parasite"
else
  for D in $BAD_DIRS; do
    [ -d "$D" ] || continue
    echo -e "$INFO Dossier parasite detecte : $D/"
    while IFS= read -r -d '' F <&3; do
      REL="${F#"$D"/}"
      if [ -e "$REL" ]; then
        if cmp -s "$F" "$REL"; then
          :  # identique : rien a copier
        else
          echo -e "$WARN $REL existe deja dans le projet (version de main) et differe de $F"
          echo "    --- differences (main -> ta version) ---"
          diff -u "$REL" "$F" | head -15
          read -rp "    Garder la version de [m]ain ou la [t]ienne ? (m/t, defaut m) " CH
          if [[ "$CH" =~ ^[tT]$ ]]; then
            cp "$F" "$REL"; echo "  remplace par ta version : $REL"
          else
            echo "  version de main gardee : $REL"
          fi
        fi
      else
        mkdir -p "$(dirname "$REL")"
        cp "$F" "$REL"
        echo "  deplace : $REL"
      fi
    done 3< <(git ls-files -z -- "$D")
    git rm -r -q "$D"
    rm -rf "$D"
    echo -e "$OK $D/ supprime (contenu replace dans le projet)"
  done
fi

# ---------------------------------------------------------------------
step "6. .env hors de git"
# ---------------------------------------------------------------------
if git ls-files --error-unmatch .env >/dev/null 2>&1; then
  git rm --cached -q .env
  echo -e "$OK .env retire du suivi git"
fi
if ! grep -qxF '.env' .gitignore 2>/dev/null; then
  echo '.env' >> .gitignore
  echo -e "$OK .env ajoute a .gitignore"
fi
[ -n "$BACKUP" ] && cp "$BACKUP" .env && echo -e "$OK Ton .env local est restaure"

# ---------------------------------------------------------------------
step "7. Verifications"
# ---------------------------------------------------------------------
if [ -n "$BLOC" ]; then
  MAUVAIS="$(git diff --name-only --diff-filter=A main...HEAD -- database/migrations 2>/dev/null | grep -v "2026_10_16_${BLOC}" || true)"
  if [ -n "$MAUVAIS" ]; then
    echo -e "$WARN Migrations sans le prefixe 2026_10_16_${BLOC}NNNNN_ (voir TODOLIST.md) :"
    echo "$MAUVAIS"
  fi
fi

if [ -f artisan ] && command -v php >/dev/null 2>&1; then
  if command -v composer >/dev/null 2>&1 && [ -f composer.json ]; then
    composer install --no-interaction -q || echo -e "$WARN composer install a echoue"
  fi
  php artisan config:clear >/dev/null 2>&1
  echo -e "$INFO php artisan migrate ..."
  if php artisan migrate --no-interaction; then
    echo -e "$OK Migrations OK"
  else
    echo -e "$ERR php artisan migrate a echoue. Corrige avant de pousser (ton code est deja commit en local, rien n'est pousse)."
    git add -A; git commit -q -m "$BRANCH: mise a jour avec main, fichiers replaces" 2>/dev/null
    exit 1
  fi
else
  echo -e "$INFO artisan/php introuvable : migrations non testees"
fi

# ---------------------------------------------------------------------
step "8. Commit et resume"
# ---------------------------------------------------------------------
LEFT="$(find . -name '*.bloc.new' -not -path './vendor/*' -not -path './node_modules/*' | head -5)"
[ -n "$LEFT" ] && die "Il reste des fichiers .bloc.new : $LEFT (supprime-les apres comparaison)."
git add -A
if ! git diff --cached --quiet; then
  git commit -q -m "$BRANCH: mise a jour avec main, fichiers replaces, .env retire de git"
  echo -e "$OK Commit cree"
fi

echo; echo "Contenu reel de la branche par rapport a main (dossiers de 1er/2e niveau) :"
git diff --name-only main...HEAD | cut -d/ -f1-2 | sort | uniq -c
SUSPECT="$(git diff --name-only --diff-filter=ACMR main...HEAD | grep -E '^(\.env$|bloc[0-9])' || true)"
if [ -n "$SUSPECT" ]; then
  echo -e "$ERR Fichiers suspects encore presents :"; echo "$SUSPECT"
  die "Rien n'est pousse. Corrige puis relance."
fi

# ---------------------------------------------------------------------
step "9. Push"
# ---------------------------------------------------------------------
if ask "Pousser $BRANCH vers GitHub maintenant ?"; then
  git push -u origin "$BRANCH" && echo -e "$OK Pousse. Ouvre maintenant la Pull Request sur GitHub."
else
  echo -e "$INFO Rien n'est pousse. Plus tard :  git push -u origin $BRANCH"
fi