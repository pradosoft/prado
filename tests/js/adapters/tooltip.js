/**
 * Adapter: tooltip
 *
 * Exposes Prado.WebUI.TTooltip as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TTooltip }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/tooltip.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/tooltip.js');

export const TTooltip = global.Prado.WebUI.TTooltip;
