/**
 * Adapter: ratings
 *
 * Exposes the public API of ratings.js (and its dependencies) as named
 * exports.
 *
 * ESM migration path — replace this file with direct named exports from
 * the source module.  Test files importing from this adapter require no
 * changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order (mirrors packages.php graph).
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/ratings/ratings.js');

export const TRatingList       = global.Prado.WebUI.TRatingList;
export const TActiveRatingList = global.Prado.WebUI.TActiveRatingList;
