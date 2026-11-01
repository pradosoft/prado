/**
 * Adapter: keyshortcuts
 *
 * Exposes Prado.KeyShortcuts as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { KeyShortcuts }
 *     from '../../../framework/Web/Javascripts/source/prado/keyshortcuts/keyshortcuts.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/keyshortcuts/keyshortcuts.js');

export const KeyShortcuts = global.Prado.KeyShortcuts;
