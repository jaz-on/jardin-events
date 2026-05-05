#!/usr/bin/env bash
# i18n hygiene: regenerate POT, update PO via msgmerge, recompile MO,
# and fail if any translation is missing or malformed.
#
# Run manually: ./bin/i18n-check.sh
# Or wire as a pre-commit hook (.git/hooks/pre-commit) or CI step.
set -euo pipefail

SLUG="jardin-events"
LOCALES=(fr_FR)
LANG_DIR="languages"
POT_PATH="${LANG_DIR}/${SLUG}.pot"

if ! command -v wp >/dev/null 2>&1; then
  echo "[i18n] wp-cli not found in PATH" >&2
  exit 1
fi
if ! command -v msgfmt >/dev/null 2>&1; then
  echo "[i18n] msgfmt (gettext) not found in PATH" >&2
  exit 1
fi

mkdir -p "$LANG_DIR"

echo "[i18n] regenerating $POT_PATH"
wp i18n make-pot . "$POT_PATH" \
  --domain="$SLUG" --skip-audit \
  --exclude=build,node_modules,vendor,_dev \
  --headers='{"Last-Translator":"Jason Rouet","Language-Team":"French"}' \
  >/dev/null

for locale in "${LOCALES[@]}"; do
  po="${LANG_DIR}/${SLUG}-${locale}.po"
  mo="${LANG_DIR}/${SLUG}-${locale}.mo"
  if [[ ! -f "$po" ]]; then
    echo "[i18n] $po missing — skipping" >&2
    continue
  fi
  echo "[i18n] msgmerge into $po"
  msgmerge --update --backup=none --no-fuzzy-matching --quiet "$po" "$POT_PATH"
  echo "[i18n] msgfmt → $mo"
  msgfmt --check --statistics "$po" -o "$mo"

  # Fail if any non-header msgstr is empty.
  if msgattrib --untranslated --no-obsolete "$po" | grep -qE '^msgid "[^"]'; then
    echo "[i18n] FAIL: untranslated entries remain in $po" >&2
    msgattrib --untranslated --no-obsolete "$po" | head -20 >&2
    exit 2
  fi
done

# Generate JSON files for any JS scripts hooked via wp_set_script_translations.
for locale in "${LOCALES[@]}"; do
  po="${LANG_DIR}/${SLUG}-${locale}.po"
  [[ -f "$po" ]] || continue
  echo "[i18n] make-json from $po"
  wp i18n make-json "$po" --no-purge >/dev/null
done

echo "[i18n] OK"
