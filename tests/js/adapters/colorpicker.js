/**
 * Adapter: colorpicker
 *
 * Exposes Rico.Color and Prado.WebUI.TColorPicker as named exports so that
 * test files can import specific symbols by name.
 *
 * ESM migration path — replace the loadScript calls with direct named imports
 * from the source file.  Test files importing from this adapter require no
 * changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order.
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
// colorpicker.js defines Rico itself — no separate Rico npm package needed.
loadScript('framework/Web/Javascripts/source/prado/colorpicker/colorpicker.js');

export const Rico        = global.Rico;
export const TColorPicker = global.Prado.WebUI.TColorPicker;
export const Registry    = global.Prado.Registry;
