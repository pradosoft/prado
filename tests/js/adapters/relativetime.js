/**
 * Adapter: relativetime
 *
 * Exposes Prado.WebUI.TRelativeTime as a named export.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TRelativeTime }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/relativetime.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/relativetime.js');

export const TRelativeTime = global.Prado.WebUI.TRelativeTime;
