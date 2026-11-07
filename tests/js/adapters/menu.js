/**
 * Adapter: menu
 *
 * Exposes Prado.WebUI.TDropdownMenu and Prado.WebUI.TContextMenu as named exports.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TDropdownMenu, TContextMenu }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/menu.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/menu.js');

export const TDropdownMenu = global.Prado.WebUI.TDropdownMenu;
export const TContextMenu = global.Prado.WebUI.TContextMenu;
