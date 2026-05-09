/**
 * Adapter: logger
 *
 * Exposes the public API of logger.js (and its prado.js dependency) as
 * named exports.
 *
 * ESM migration path — replace this file with direct named exports from
 * the source module.  Test files importing from this adapter require no
 * changes.
 *
 * Note: logger.js declares its globals with bare `VarName = ...` (no var/let/
 * const), so they land directly on the global object after runInThisContext.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/logger/logger.js');

export const CustomEvent   = global.CustomEvent;
export const Cookie        = global.Cookie;
export const Logger        = global.Logger;
export const LogEntry      = global.LogEntry;
export const LogConsole    = global.LogConsole;
export const inspect       = global.inspect;
export const puts          = global.puts;
export const PradoInspector = global.Prado.Inspector;
