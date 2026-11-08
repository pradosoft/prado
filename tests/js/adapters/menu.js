/**
 * Adapter: menu
 *
 * Exposes Prado.WebUI.TDropdownMenu as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TDropdownMenu }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/menu.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/menu.js');

export const TDropdownMenu = global.Prado.WebUI.TDropdownMenu;
