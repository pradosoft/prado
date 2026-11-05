// @ts-check
import { readdirSync, rmSync } from 'node:fs';
import { join } from 'node:path';

/**
 * Global setup: clear published asset caches before every Playwright run.
 *
 * Prado's TAssetManager publishes framework JS/CSS into an `assets/<hash>/`
 * directory on first request and stamps each published file with the source
 * file's modification time. A later source edit whose timestamp ends up equal to
 * the stamp is not re-published, so the browser keeps loading the previous
 * content. Tests then run against pre-edit code and report failures that have
 * nothing to do with the change, or pass over a regression that is still live in
 * the source.
 *
 * Both test trees publish into this layout, so both are cleared:
 *
 *   vendor/pradosoft/prado-demos/<demo>/assets/<hash>/
 *   tests/harness/<group>/assets/<hash>/
 *
 * Continuous integration starts from a fresh checkout and has no caches, so a
 * stale cache makes local results disagree with CI. Clearing both keeps them in
 * step. Prado re-publishes on the next page request, at the cost of about a
 * second of first-page latency per application.
 */

// Each root holds one directory per application, each with its own assets/ dir.
const ASSET_ROOTS = ['vendor/pradosoft/prado-demos', 'tests/harness'];

function subdirectories(path) {
	try {
		return readdirSync(path, { withFileTypes: true }).filter((entry) => entry.isDirectory());
	} catch {
		// Path absent or unreadable: nothing published there.
		return [];
	}
}

export default function globalSetup() {
	let cleared = 0;

	for (const root of ASSET_ROOTS) {
		for (const app of subdirectories(root)) {
			const assetsDir = join(root, app.name, 'assets');
			for (const hashDir of subdirectories(assetsDir)) {
				try {
					rmSync(join(assetsDir, hashDir.name), { recursive: true, force: true });
					cleared++;
				} catch {
					/* ignore — best-effort */
				}
			}
		}
	}

	if (cleared > 0) {
		console.log(`[global-setup] Cleared ${cleared} stale Prado asset cache dir(s).`);
	}
}
