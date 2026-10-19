#!/usr/bin/env bash
set -euo pipefail
[ -f artisan ] || { echo "Lancez ce script depuis la racine du projet." >&2; exit 1; }

python3 - <<'PY'
import re, pathlib

M = pathlib.Path("database/migrations")
migs = [M / "2026_10_16_300001_create_employes_table.php",
        M / "2026_10_16_300002_create_proposition_embauches_table.php"]
modeles = {
    "app/Models/Embauche/Employe.php": "embauche_employes",
    "app/Models/Embauche/PropositionEmbauche.php": "embauche_proposition_embauches",
}
noms = {"employes": "embauche_employes",
        "proposition_embauches": "embauche_proposition_embauches"}

def sauver(p, texte):
    p = pathlib.Path(p)
    if not p.with_suffix(p.suffix + ".bak").exists():
        p.with_suffix(p.suffix + ".bak").write_text(p.read_text())
    p.write_text(texte)

for p in migs:
    t = p.read_text()
    for ancien, nouveau in noms.items():
        t = re.sub(r"(['\"])%s\1" % ancien, r"\1%s\1" % nouveau, t)
    t = re.sub(r"(foreignId\('employe_id'\)(?:\s*->\w+\([^)]*\))*?\s*->constrained\()\)",
               r"\1'embauche_employes')", t)
    sauver(p, t)
    print("migration corrigée :", p.name)

for chemin, table in modeles.items():
    p = pathlib.Path(chemin)
    t = p.read_text()
    if re.search(r"\$table\s*=", t):
        t = re.sub(r"(\$table\s*=\s*)(['\"])[^'\"]*\2", r"\1'%s'" % table, t)
    else:
        t = re.sub(r"(class\s+\w+\s+extends\s+\w+[^{]*\{)",
                   r"\1\n    protected $table = '%s';\n" % table, t, count=1)
    sauver(p, t)
    print("modèle corrigé     :", p.name)
PY

echo
echo "=== A vérifier : constrained() sans nom de table ==="
grep -n "constrained()" database/migrations/2026_10_16_30000*.php || echo "(aucune)"
echo
echo "=== Références aux tables du bloc 3 ==="
grep -rn "embauche_employes\|embauche_proposition" database/migrations app | grep -v "\.bak" || true
