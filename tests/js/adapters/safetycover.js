/**
 * Adapter: safetycover
 *
 * Exposes Prado.WebUI.TSafetyCover as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TSafetyCover }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/safetycover.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/safetycover.js');

export const TSafetyCover = global.Prado.WebUI.TSafetyCover;
