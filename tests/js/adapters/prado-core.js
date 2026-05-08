/**
 * Adapter: prado-core
 *
 * Exposes the public API of prado.js as named exports so that test files
 * can import specific symbols by name rather than going through the global
 * Prado object.  This makes the tests ESM-ready: when the source is
 * converted to ES Modules this file simply becomes:
 *
 *   export { RequestManager, Element, PostBack, Registry, Version }
 *     from '../../../framework/Web/Javascripts/source/prado/prado.js';
 *
 * and every test file that imports from this adapter continues to work
 * without a single change.
 *
 * NOTE: prado.js also extends String.prototype and Date.prototype as
 * side-effects.  Those extensions are available in all test files in the
 * same worker after this adapter is imported, exactly as they would be in
 * a browser after the <script> tag is evaluated.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');

// Re-export top-level API pieces by name.
export const Version         = global.Prado.Version;
export const Registry        = global.Prado.Registry;
export const RequestManager  = global.Prado.RequestManager;
export const Element         = global.Prado.Element;
export const PostBack        = global.Prado.PostBack;
