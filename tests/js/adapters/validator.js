/**
 * Adapter: validator
 *
 * Exposes the public API of validation3.js as named exports.
 *
 * ESM migration path — replace this file with:
 *
 *   export { Validation, ValidationManager, WebUI }
 *     from '../../../framework/Web/Javascripts/source/prado/validator/validation3.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order.
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/activecontrols/ajax3.js');
loadScript('framework/Web/Javascripts/source/prado/validator/validation3.js');

export const Validation        = global.Prado.Validation;
export const ValidationManager = global.Prado.ValidationManager;
// Prado.WebUI holds all validator classes (TBaseValidator, TRequiredFieldValidator, etc.)
export const WebUI             = global.Prado.WebUI;
