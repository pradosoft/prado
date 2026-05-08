/**
 * Adapter: ajax
 *
 * Exposes the public API of ajax3.js (and its dependencies prado.js and
 * controls.js) as named exports.
 *
 * ESM migration path — replace this file with:
 *
 *   export { CallbackRequestManager, CallbackRequest, Callback }
 *     from '../../../framework/Web/Javascripts/source/prado/activecontrols/ajax3.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order (mirrors packages.php graph).
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/activecontrols/ajax3.js');

export const CallbackRequestManager = global.Prado.CallbackRequestManager;
export const CallbackRequest        = global.Prado.CallbackRequest;
export const Callback               = global.Prado.Callback;
