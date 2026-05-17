#!/usr/bin/env bash
# Sets up vendor/pradosoft/prado-demos for functional tests.
# Clones on first run; pulls the latest on subsequent runs.
# Installs prado-demos' own Composer dependencies, then replaces
# the pradosoft/prado dep with a symlink to the current repo so
# the dev branch is what the demo apps actually run against.
set -euo pipefail

DEMOS_DIR="vendor/pradosoft/prado-demos"

if [ -d "$DEMOS_DIR/.git" ]; then
    echo "Updating prado-demos..."
    git -C "$DEMOS_DIR" pull
else
    echo "Cloning prado-demos..."
    rm -rf "$DEMOS_DIR"
    git clone https://github.com/pradosoft/prado-demos "$DEMOS_DIR"
fi

composer update -d "$DEMOS_DIR" --no-interaction

# Replace the Packagist copy of prado with the current working tree.
rm -rf "$DEMOS_DIR/vendor/pradosoft/prado"
ln -s "$(pwd)" "$DEMOS_DIR/vendor/pradosoft/prado"

echo "prado-demos ready at $DEMOS_DIR"
