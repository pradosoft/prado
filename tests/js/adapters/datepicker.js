/**
 * Adapter: datepicker
 *
 * Exposes Prado.WebUI.TDatePicker as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TDatePicker }
 *     from '../../../framework/Web/Javascripts/source/prado/datepicker/datepicker.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order (mirrors packages.php graph).
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/datepicker/datepicker.js');

export const TDatePicker = global.Prado.WebUI.TDatePicker;
