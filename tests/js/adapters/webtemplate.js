/**
 * Adapter: webtemplate
 *
 * Exposes Prado.WebUI.TWebTemplate as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TWebTemplate }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/webtemplate.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/webtemplate.js');

export const TWebTemplate = global.Prado.WebUI.TWebTemplate;
