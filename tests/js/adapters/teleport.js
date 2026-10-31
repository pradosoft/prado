/**
 * Adapter: teleport
 *
 * Exposes Prado.WebUI.TTeleport as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TTeleport }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/teleport.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/teleport.js');

export const TTeleport = global.Prado.WebUI.TTeleport;
