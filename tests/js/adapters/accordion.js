/**
 * Adapter: accordion
 *
 * Exposes Prado.WebUI.TAccordion as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TAccordion }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/accordion.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/accordion.js');

export const TAccordion = global.Prado.WebUI.TAccordion;
