/**
 * Adapter: canvas
 *
 * Exposes Prado.WebUI.TCanvas as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TCanvas }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/canvas.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/canvas.js');

export const TCanvas = global.Prado.WebUI.TCanvas;
