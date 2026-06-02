// @ts-check
import { readdirSync, rmSync, statSync } from 'node:fs';
import { join } from 'node:path';

/**
 * Global setup: clear demo asset caches before every Playwright run.
 *
 * Prado's TAssetManager publishes framework JS/CSS into
 * vendor/pradosoft/prado-demos/<demo>/assets/<hash>/ on first request. The
 * published file is the minified output of the source at publish time —
 * subsequent source edits do NOT invalidate that cache, so the browser
 * keeps loading stale prado.min.js / controls.min.js / etc. Until those
 * hashed directories are removed, the tests run against pre-edit code
 * and report unrelated failures.
 *
 * This setup walks vendor/pradosoft/prado-demos/<demo>/assets/ and removes
 * every <hash>/ subdirectory once per `npx playwright test` invocation.
 * Prado re-publishes assets on the next page request, so there is no
 * downside other than ~1s of first-page latency per demo.
 */
export default function globalSetup() {
	const demosRoot = 'vendor/pradosoft/prado-demos';
	let cleared = 0;
	let demos;
	try {
		demos = readdirSync(demosRoot, { withFileTypes: true })
			.filter((d) => d.isDirectory())
			.map((d) => join(demosRoot, d.name, 'assets'));
	} catch {
		// prado-demos not installed — nothing to clear.
		return;
	}

	for (const assetsDir of demos) {
		let entries;
		try {
			entries = readdirSync(assetsDir, { withFileTypes: true });
		} catch {
			continue;
		}
		for (const entry of entries) {
			if (!entry.isDirectory()) continue;
			const sub = join(assetsDir, entry.name);
			try {
				rmSync(sub, { recursive: true, force: true });
				cleared++;
			} catch {
				/* ignore — best-effort */
			}
		}
	}

	if (cleared > 0) {
		// eslint-disable-next-line no-console
		console.log(`[global-setup] Cleared ${cleared} stale Prado asset cache dir(s).`);
	}
}
