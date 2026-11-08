/**
 * Adapter: confirmsubmit
 *
 * Exposes Prado.WebUI.TConfirmSubmit as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TConfirmSubmit }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/confirmsubmit.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/confirmsubmit.js');

export const TConfirmSubmit = global.Prado.WebUI.TConfirmSubmit;
