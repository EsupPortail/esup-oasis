#!/bin/sh
# Réinstalle les dépendances au démarrage si le yarn.lock monté a changé
# (changement de branche, mise à jour des packages, etc.).
set -e

STAMP=node_modules/.yarn-lock.sha
CURRENT=$(sha256sum yarn.lock | cut -d' ' -f1)

if [ ! -f "$STAMP" ] || [ "$(cat "$STAMP")" != "$CURRENT" ]; then
  echo "[entrypoint-dev] yarn.lock modifié, installation des dépendances..."
  yarn install --frozen-lockfile
  echo "$CURRENT" > "$STAMP"
fi

exec "$@"
