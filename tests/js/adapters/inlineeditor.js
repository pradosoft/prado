/**
 * Adapter: inlineeditor
 *
 * Exposes the public API of inlineeditor.js (and its dependency chain)
 * as named exports so that test files can import specific symbols by name.
 *
 * ESM migration path — replace the loadScript calls with direct imports
 * from the source files. Test files importing from this adapter require no
 * changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order.
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/activecontrols/ajax3.js');
loadScript('framework/Web/Javascripts/source/prado/activecontrols/inlineeditor.js');

// Callback infrastructure (needed for mocking in tests)
export const CallbackRequest    = global.Prado.CallbackRequest;
export const Registry           = global.Prado.Registry;

// The control under test
export const TInPlaceTextBox    = global.Prado.WebUI.TInPlaceTextBox;
