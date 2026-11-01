/**
 * Adapter: unsavedchanges
 *
 * Exposes Prado.WebUI.TUnsavedChangesGuard as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TUnsavedChangesGuard }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/unsavedchanges.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/unsavedchanges.js');

export const TUnsavedChangesGuard = global.Prado.WebUI.TUnsavedChangesGuard;
