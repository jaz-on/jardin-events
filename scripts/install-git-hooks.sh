#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SOURCE_HOOK="$ROOT_DIR/scripts/git-hooks/pre-push"
TARGET_HOOK="$ROOT_DIR/.git/hooks/pre-push"

if [[ ! -d "$ROOT_DIR/.git" ]]; then
	echo "ERROR: .git directory not found in $ROOT_DIR"
	exit 1
fi

if [[ ! -f "$SOURCE_HOOK" ]]; then
	echo "ERROR: missing source hook: $SOURCE_HOOK"
	exit 1
fi

cp "$SOURCE_HOOK" "$TARGET_HOOK"
chmod +x "$TARGET_HOOK"

echo "Installed pre-push hook to $TARGET_HOOK"
