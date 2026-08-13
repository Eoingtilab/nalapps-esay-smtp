#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="$ROOT_DIR/dist"
PLUGIN_DIR="nalapps-easy-smtp"

rm -rf "$OUT_DIR"
mkdir -p "$OUT_DIR/$PLUGIN_DIR"
rsync -a --exclude='.git' --exclude='dist' --exclude='tools' --exclude='.github' --exclude='tests' --exclude='phpcs.xml.dist' "$ROOT_DIR/" "$OUT_DIR/$PLUGIN_DIR/"
cd "$OUT_DIR"
zip -r "nalapps-easy-smtp.zip" "$PLUGIN_DIR"
sha256sum "nalapps-easy-smtp.zip" > "nalapps-easy-smtp.zip.sha256"
