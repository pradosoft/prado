#!/usr/bin/env bash
# Sets up vendor/pradosoft/prado-demos for functional tests.
# Clones on first run; pulls the latest on subsequent runs. Installs the demos'
# own Composer dependencies, then installs any framework dependencies added on the
# dev branch that the released framework's dependency set does not provide, and
# finally symlinks the working-tree prado in place of the released one so the dev
# branch is what the demo apps actually run against.
set -euo pipefail

DEMOS_DIR="vendor/pradosoft/prado-demos"

if [ -d "$DEMOS_DIR/.git" ]; then
    echo "Updating prado-demos..."
    git -C "$DEMOS_DIR" reset --hard   # discard the composer.json/lock edits made below on a prior run
    git -C "$DEMOS_DIR" pull
else
    echo "Cloning prado-demos..."
    rm -rf "$DEMOS_DIR"
    git clone https://github.com/pradosoft/prado-demos "$DEMOS_DIR"
fi

composer update -d "$DEMOS_DIR" --no-interaction

# The released framework resolved above may lack runtime dependencies added on the
# dev branch (e.g. laravel/serializable-closure). Install any of the working tree's
# requires that are missing from the demos vendor, so the dev framework symlinked
# below has its dependencies. Computed from the working tree, so it stays correct as
# the framework's dependencies change. A partial require leaves the rest of the
# already-resolved tree untouched.
MISSING=$(php -r '
    $demos = $argv[1];
    $req = json_decode(file_get_contents("composer.json"), true)["require"] ?? [];
    $inst = json_decode(file_get_contents($demos . "/vendor/composer/installed.json"), true);
    $have = [];
    foreach (($inst["packages"] ?? $inst) as $p) { $have[strtolower($p["name"])] = true; }
    $out = [];
    foreach ($req as $name => $ver) {
        if ($name === "php" || str_starts_with($name, "ext-")) { continue; }
        if (empty($have[strtolower($name)])) { $out[] = $name . ":" . $ver; }
    }
    echo implode(" ", $out);
' "$DEMOS_DIR")
if [ -n "$MISSING" ]; then
    echo "Installing dev-branch framework deps missing from demos: $MISSING"
    composer require -d "$DEMOS_DIR" --no-interaction $MISSING
fi

# Replace the Packagist copy of prado with the current working tree.
rm -rf "$DEMOS_DIR/vendor/pradosoft/prado"
ln -s "$(pwd)" "$DEMOS_DIR/vendor/pradosoft/prado"

echo "prado-demos ready at $DEMOS_DIR"
