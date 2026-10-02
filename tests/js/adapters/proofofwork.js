/**
 * Adapter: proofofwork
 *
 * Exposes Prado.WebUI.TProofOfWork and Prado.ProofOfWorkSolver as named exports.
 *
 * ESM migration path — replace this file with:
 *
 *   export { TProofOfWork }
 *     from '../../../framework/Web/Javascripts/source/prado/controls/proofofwork.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/controls/proofofwork-solver.js');
loadScript('framework/Web/Javascripts/source/prado/controls/proofofwork.js');

export const TProofOfWork = global.Prado.WebUI.TProofOfWork;
export const ProofOfWorkSolver = global.Prado.ProofOfWorkSolver;
