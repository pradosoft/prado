/**
 * Adapter: tabpanel
 *
 * Exposes Prado.WebUI.TTabPanel as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TTabPanel }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/tabpanel.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/tabpanel.js');

export const TTabPanel = global.Prado.WebUI.TTabPanel;
