/**
 * Adapter: slider
 *
 * Exposes Prado.WebUI.TSlider as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TSlider }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/slider.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/slider.js');

export const TSlider = global.Prado.WebUI.TSlider;
