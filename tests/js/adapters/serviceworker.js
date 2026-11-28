/**
 * Adapter: serviceworker
 *
 * Exposes Prado.ServiceWorker as a named export.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/serviceworker/serviceworker.js');

export const ServiceWorker = global.Prado.ServiceWorker;
