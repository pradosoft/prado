/**
 * Adapter: keyboard
 *
 * Exposes Prado.WebUI.TKeyboard as a named export so that test files can
 * import specific symbols by name.
 *
 * ESM migration path — replace the loadScript calls with direct named imports
 * from the source file.  Test files importing from this adapter require no
 * changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order.
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/keyboard.js');

export const TKeyboard = global.Prado.WebUI.TKeyboard;
export const Registry  = global.Prado.Registry;
