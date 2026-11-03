/**
 * Adapter: serverevents
 *
 * Exposes the public API of serverevents.js (and its dependency chain) as
 * named exports.
 *
 * ESM migration path — replace the loadScript calls with direct imports
 * from the source files. Test files importing from this adapter require no
 * changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order (mirrors the packages.php graph).
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/activecontrols/ajax3.js');
loadScript('framework/Web/Javascripts/source/prado/activecontrols/serverevents.js');

export const ServerEvents                  = global.Prado.ServerEvents;
export const EventSourceTransport          = global.Prado.EventSource;
export const TServerEventTriggeredCallback = global.Prado.WebUI.TServerEventTriggeredCallback;
export const Registry                      = global.Prado.Registry;
