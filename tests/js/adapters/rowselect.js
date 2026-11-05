/**
 * Adapter: rowselect
 *
 * Exposes Prado.WebUI.TRowSelectColumn as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TRowSelectColumn }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/rowselect.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/rowselect.js');

export const TRowSelectColumn = global.Prado.WebUI.TRowSelectColumn;
