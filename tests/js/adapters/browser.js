/**
 * Adapter: browser
 *
 * Exposes Prado.Browser and Prado.Notification from controls/browser.js
 * (and its dependency chain) as named exports.
 *
 * ESM migration path — replace the loadScript calls with direct imports
 * from the source files. Test files importing from this adapter require no
 * changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order.
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/browser.js');

export const Browser      = global.Prado.Browser;
export const Notification = global.Prado.Notification;
