/**
 * Adapter: drawer
 *
 * Exposes Prado.WebUI.TDrawer as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TDrawer }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/drawer.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/drawer.js');

export const TDrawer = global.Prado.WebUI.TDrawer;
