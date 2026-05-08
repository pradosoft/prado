/**
 * Adapter: activedatepicker
 *
 * Exposes Prado.WebUI.TActiveDatePicker as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TActiveDatePicker }
 *     from '../../../framework/Web/Javascripts/source/prado/activecontrols/activedatepicker.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order.
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/activecontrols/ajax3.js');
loadScript('framework/Web/Javascripts/source/prado/datepicker/datepicker.js');
loadScript('framework/Web/Javascripts/source/prado/activecontrols/activedatepicker.js');

export const TActiveDatePicker = global.Prado.WebUI.TActiveDatePicker;
