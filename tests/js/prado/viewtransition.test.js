/**
 * Tests for Prado.Element.canViewTransition() and Prado.Element.viewTransition().
 *
 * jsdom has no document.startViewTransition; a fake records the calls, runs the
 * update when a test captures the old state, and settles the transition promises.
 *
 * Source: framework/Web/Javascripts/source/prado/prado.js
 */

import { Element } from '../adapters/prado-core.js';

describe('Prado.Element view transitions', () => {
	let reducedMotion, transitions, visibility;

	// A ViewTransition whose update runs when capture() is called, as the browser runs it in a later frame.
	function fakeStart(update) {
		let resolveDone, rejectDone, resolveFinished, rejectReady;
		const t = {
			update,
			skipped: false,
			updateCallbackDone: new Promise((resolve, reject) => { resolveDone = resolve; rejectDone = reject; }),
			ready: new Promise((resolve, reject) => { rejectReady = reject; }),
			finished: new Promise((resolve) => { resolveFinished = resolve; }),
			skipTransition: vi.fn(() => { t.skipped = true; }),
			capture() {
				try {
					update();
					resolveDone();
				} catch (e) {
					rejectDone(e);
					rejectReady(e);
				}
				resolveFinished();
			},
		};
		transitions.push(t);
		return t;
	}

	beforeEach(() => {
		reducedMotion = false;
		visibility = 'visible';
		transitions = [];
		document.startViewTransition = vi.fn(fakeStart);
		vi.stubGlobal('matchMedia', (query) => ({
			matches: reducedMotion && query === '(prefers-reduced-motion: reduce)',
		}));
		Object.defineProperty(document, 'visibilityState', { get: () => visibility, configurable: true });
		Element._viewTransition = null;
	});

	afterEach(() => {
		delete document.startViewTransition;
		delete document.visibilityState;
		vi.unstubAllGlobals();
		Element._viewTransition = null;
	});

	describe('canViewTransition', () => {
		it('is true with support, a visible page, and no reduced motion', () => {
			expect(Element.canViewTransition()).toBe(true);
		});

		it('is false without document.startViewTransition', () => {
			delete document.startViewTransition;
			expect(Element.canViewTransition()).toBe(false);
		});

		it('is false under prefers-reduced-motion: reduce', () => {
			reducedMotion = true;
			expect(Element.canViewTransition()).toBe(false);
		});

		it('is false on a hidden page', () => {
			visibility = 'hidden';
			expect(Element.canViewTransition()).toBe(false);
		});
	});

	describe('viewTransition', () => {
		it('runs the update inside a transition after the old state is captured', async () => {
			const update = vi.fn();
			const done = Element.viewTransition(update);
			expect(document.startViewTransition).toHaveBeenCalledTimes(1);
			expect(update).not.toHaveBeenCalled();
			transitions[0].capture();
			await expect(done).resolves.toBeUndefined();
			expect(update).toHaveBeenCalledTimes(1);
		});

		it.each([
			['reduced motion', () => { reducedMotion = true; }],
			['no support', () => { delete document.startViewTransition; }],
			['a hidden page', () => { visibility = 'hidden'; }],
		])('runs the update at once under %s', async (name, setup) => {
			setup();
			const update = vi.fn();
			const done = Element.viewTransition(update);
			expect(update).toHaveBeenCalledTimes(1);
			expect(transitions).toHaveLength(0);
			await expect(done).resolves.toBeUndefined();
		});

		it('rejects with the error of an update run at once', async () => {
			reducedMotion = true;
			const error = new Error('update');
			await expect(Element.viewTransition(() => { throw error; })).rejects.toBe(error);
		});

		it('rejects with the error of an update inside a transition', async () => {
			const error = new Error('update');
			const done = Element.viewTransition(() => { throw error; });
			transitions[0].capture();
			await expect(done).rejects.toBe(error);
		});

		it('skips the running transition when a new one starts', () => {
			Element.viewTransition(() => {});
			Element.viewTransition(() => {});
			expect(transitions[0].skipTransition).toHaveBeenCalledTimes(1);
			expect(transitions[1].skipTransition).not.toHaveBeenCalled();
			expect(Element._viewTransition).toBe(transitions[1]);
		});

		it('forgets the transition once it finished', async () => {
			Element.viewTransition(() => {});
			transitions[0].capture();
			await transitions[0].finished;
			await Promise.resolve();
			expect(Element._viewTransition).toBeNull();
			Element.viewTransition(() => {});
			expect(transitions[0].skipTransition).not.toHaveBeenCalled();
		});
	});
});
