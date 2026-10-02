/**
 * Tests for the TProofOfWork client class and its solver.
 * Sources: framework/Web/Javascripts/source/prado/controls/proofofwork.js
 *          framework/Web/Javascripts/source/prado/controls/proofofwork-solver.js
 *
 * jsdom has no Worker, so the control solves on the main thread.
 */

import { createHash } from 'crypto';
import { TProofOfWork, ProofOfWorkSolver } from '../adapters/proofofwork.js';

const ID = 'pow';
const sha256 = (text) => createHash('sha256').update(text).digest('hex');

function makeChallenge(number = 37, max = 100) {
	const salt = 'abcdef0123456789abcdef01?expires=9999999999';
	return { algorithm: 'SHA-256', challenge: sha256(salt + number), salt, maxnumber: max, signature: 'sig' };
}

let instances = [];

function makeControl(extra = {}) {
	const form = document.createElement('form');
	form.id = 'form1';
	form.innerHTML = `<input id="name" type="text"><div id="${ID}" role="status" aria-busy="false">`
		+ `<span class="pow-status"></span><input type="hidden" id="${ID}_solution" name="${ID}" value=""></div>`;
	document.body.appendChild(form);
	form.submit = vi.fn();
	const control = new TProofOfWork(Object.assign({
		ID,
		Challenge: makeChallenge(),
		StartMode: 'Submit',
		WorkerUrl: '',
		VerifyingText: 'Verifying…',
		VerifiedText: 'Verified',
		FailedText: 'Failed'
	}, extra));
	instances.push(control);
	return { control, form, field: document.getElementById(`${ID}_solution`), region: document.getElementById(ID) };
}

function submitEvent(form) {
	const event = new Event('submit', { bubbles: true, cancelable: true });
	form.dispatchEvent(event);
	return event;
}

afterEach(() => {
	for (const c of instances) c.deinitialize();
	instances = [];
	document.body.innerHTML = '';
	for (const k of Object.keys(global.Prado.Registry)) delete global.Prado.Registry[k];
});

describe('ProofOfWorkSolver.sha256Hex', () => {
	it.each([
		[''],
		['abc'],
		['abcdbcdecdefdefgefghfghighijhijkijkljklmklmnlmnomnopnopq'],
		['a'.repeat(55)],
		['a'.repeat(56)],
		['a'.repeat(64)],
		['a'.repeat(200)],
	])('matches Node crypto for %j', (text) => {
		expect(ProofOfWorkSolver.sha256Hex(text)).toBe(sha256(text));
	});
});

describe('ProofOfWorkSolver.solve', () => {
	it('finds the number', () => {
		const c = makeChallenge(73);
		expect(ProofOfWorkSolver.solve(c.challenge, c.salt, 0, c.maxnumber)).toBe(73);
	});

	it('returns -1 outside the range', () => {
		const c = makeChallenge(73);
		expect(ProofOfWorkSolver.solve(c.challenge, c.salt, 0, 50)).toBe(-1);
	});

	it('solves in chunks asynchronously', async () => {
		const c = makeChallenge(73);
		await expect(ProofOfWorkSolver.solveAsync(c.challenge, c.salt, c.maxnumber, 10)).resolves.toBe(73);
	});
});

describe('TProofOfWork', () => {
	it('starts on load and writes the solution', async () => {
		const { control, field, region } = makeControl({ StartMode: 'Load' });
		expect(region.getAttribute('aria-busy')).toBe('true');
		expect(region.querySelector('.pow-status').textContent).toBe('Verifying…');
		await expect(control.promise).resolves.toBe(true);
		expect(JSON.parse(field.value)).toEqual({ challenge: makeChallenge().challenge, salt: makeChallenge().salt, signature: 'sig', number: 37 });
		expect(region.getAttribute('aria-busy')).toBe('false');
		expect(region.querySelector('.pow-status').textContent).toBe('Verified');
	});

	it('starts on the first focus inside the form', async () => {
		const { control, form } = makeControl({ StartMode: 'Focus' });
		expect(control.promise).toBeNull();
		form.querySelector('#name').dispatchEvent(new Event('focusin', { bubbles: true }));
		await expect(control.promise).resolves.toBe(true);
	});

	it('holds a postback until solved, then submits once', async () => {
		const { control, form } = makeControl();
		const first = submitEvent(form);
		const second = submitEvent(form);
		expect(first.defaultPrevented).toBe(true);
		expect(second.defaultPrevented).toBe(true);
		await control.promise;
		await Promise.resolve();
		expect(form.submit).toHaveBeenCalledTimes(1);
	});

	it('lets submissions through once solved', async () => {
		const { control, form } = makeControl({ StartMode: 'Load' });
		await control.promise;
		expect(submitEvent(form).defaultPrevented).toBe(false);
	});

	it('reports failure and does not submit when no solution exists', async () => {
		const { control, form, field, region } = makeControl({ Challenge: makeChallenge(500, 100) });
		submitEvent(form);
		await expect(control.promise).resolves.toBe(false);
		await Promise.resolve();
		expect(form.submit).not.toHaveBeenCalled();
		expect(field.value).toBe('');
		expect(region.querySelector('.pow-status').textContent).toBe('Failed');
	});
});
