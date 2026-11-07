/**
 * Adapter: sharedconnection
 *
 * Exposes Prado.SharedConnection as a named export.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/sharedconnection/sharedconnection.js');

export const SharedConnection = global.Prado.SharedConnection;
