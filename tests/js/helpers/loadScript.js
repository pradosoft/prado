/**
 * loadScript — execute a PRADO source script in the current V8 context.
 *
 * Vitest runs each test file in its own worker with globals: true and the
 * jsdom environment, meaning window/document/jQuery are already on global
 * before this helper is called.
 *
 * vm.runInThisContext is used instead of eval() or dynamic import() because:
 *   - Top-level `var` declarations become properties of the global object,
 *     matching their behaviour in a browser <script> tag.
 *   - It does NOT create a new module scope, so the scripts can read and
 *     write globals freely.
 *
 * When the PRADO source files are converted to ES Modules (ESM), the
 * adapter files (tests/js/adapters/*.js) will be updated to use direct
 * `import … from` statements, and this helper will no longer be needed.
 * The *test* files themselves will require no changes.
 */

import { readFileSync } from 'fs';
import { resolve } from 'path';
import { runInThisContext } from 'vm';
import jQuery from 'jquery';

// Ensure jQuery is on global before any PRADO script is loaded.
// PRADO scripts call jQuery.klass(), jQuery.extend(), etc. at parse time.
if (typeof global.jQuery === 'undefined') {
	global.jQuery = jQuery;
	global.$ = jQuery;
}

/**
 * Load a PRADO source file into the current test worker's global context.
 *
 * @param {string} relativePath - Path relative to the project root,
 *   e.g. 'framework/Web/Javascripts/source/prado/prado.js'
 */
export function loadScript(relativePath) {
	const fullPath = resolve(process.cwd(), relativePath);
	const code = readFileSync(fullPath, 'utf-8');
	runInThisContext(code, { filename: fullPath, displayErrors: true });
}
