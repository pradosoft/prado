/**
 * Adapter: htmlarea5
 *
 * Exposes Prado.WebUI.THtmlArea5 as a named export so that test files can
 * import specific symbols by name.
 *
 * tinyMCE must be set up on global BEFORE this adapter is imported (or at
 * least before a THtmlArea5 instance is constructed).  The test files that
 * import this adapter set global.tinyMCE in a beforeEach / module-level
 * setup block.
 *
 * ESM migration path — replace the loadScript calls with direct named imports
 * from the source file.  Test files importing from this adapter require no
 * changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Provide a stub tinyMCE global so that the module-level jQuery.extend() call
// inside htmlarea5.js can reference it safely.  Individual tests override this
// via vi.spyOn or by reassigning global.tinyMCE.
if (typeof global.tinyMCE === 'undefined') {
	global.tinyMCE = {
		init:        () => {},
		get:         () => null,
		execCommand: () => {},
		editors:     [],
	};
}

// Load in dependency order.
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/htmlarea5.js');

export const THtmlArea5 = global.Prado.WebUI.THtmlArea5;
export const Registry   = global.Prado.Registry;
