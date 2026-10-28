#!/usr/bin/env bash
# Baut das Plugin-ZIP (Plugin + mitgeliefertes Theme + Prüfsummen) aus einem festen Git-Stand.
# Aufruf: tools/build-plugin-zip.sh <Tag-oder-Commit> <Version> <Ausgabeordner>
# Ergebnis: <Ausgabeordner>/michatheme-v5-plugin-<Version>.zip und .zip.sha256
set -euo pipefail
REF="${1:?Tag oder Commit}"; VER="${2:?Version x.y.z}"; OUT="${3:?Ausgabeordner}"
[[ "$VER" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Version muss x.y.z sein" >&2; exit 1; }
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
mkdir -p "$OUT" "$TMP/pkg"
OUT="$(cd "$OUT" && pwd)"
cd "$ROOT"
# Plugin-Ordner aus dem Git-Stand
# (Unterordner-Archiv, sonst greift export-ignore für plugin/ aus .gitattributes)
mkdir -p "$TMP/pkg/michatheme-v5"
git archive "$REF:plugin/michatheme-v5" | tar -x -C "$TMP/pkg/michatheme-v5"
# Theme aus demselben Stand (.gitattributes lässt plugin/ und tools/ weg)
mkdir -p "$TMP/pkg/michatheme-v5/theme/michatheme-v5"
git archive "$REF" | tar -x -C "$TMP/pkg/michatheme-v5/theme/michatheme-v5"
cp "$TMP/pkg/michatheme-v5/theme/michatheme-v5/CHANGELOG.md" "$TMP/pkg/michatheme-v5/CHANGELOG.md"
php "$ROOT/tools/make-integrity.php" "$TMP/pkg/michatheme-v5/theme/michatheme-v5" "$VER"
ZIP="$OUT/michatheme-v5-plugin-$VER.zip"
rm -f "$ZIP"
(cd "$TMP/pkg" && zip -q -r -X "$ZIP" michatheme-v5)
(cd "$OUT" && shasum -a 256 "$(basename "$ZIP")" > "$(basename "$ZIP").sha256")
echo "$ZIP"; cat "$ZIP.sha256"
