/**
 * Tests for the view transition of a callback response: Prado.CallbackRequest
 * getIsViewTransition(), applyInViewTransition(), and the onComplete and send
 * gate holds while the actions wait for the transition.
 *
 * jsdom has no document.startViewTransition; a fake runs the update when a
 * test captures the old state, as the browser runs it in a later frame.
 *
 * Source: framework/Web/Javascripts/source/prado/activecontrols/ajax3.js
 */

import { CallbackRequest, CallbackRequestManager } from '../adapters/ajax.js';

const part = (name, content) => `<!--${name}-->${content}<!--//${name}-->`;
const ACTIONS = part('X-PRADO-ACTIONS', JSON.stringify([{ vtAction: ['first'] }, { vtAction: ['second'] }]));
const TRANSITION = part('X-PRADO-VIEWTRANSITION', '1');

describe('Prado.CallbackRequest view transition', () => {
	let transitions, applied, reducedMotion;

	function fakeStart(update) {
		let resolveDone, rejectDone;
		const t = {
			updateCallbackDone: new Promise((resolve, reject) => { resolveDone = resolve; rejectDone = reject; }),
			ready: new Promise(() => {}),
			finished: new Promise(() => {}),
			skipTransition: vi.fn(),
			capture() {
				try {
					update();
					resolveDone();
				} catch (e) {
					rejectDone(e);
				}
			},
		};
		transitions.push(t);
		return t;
	}

	// Runs the success then the complete handler, as jQuery does for a response.
	function respond(request, data) {
		request.successHandler(data, 'success', { status: 200, responseText: data });
		request.completeHandler({}, 'success');
	}

	const settle = async () => {
		for (let i = 0; i < 4; i++)
			await Promise.resolve();
	};

	beforeEach(() => {
		transitions = [];
		applied = [];
		reducedMotion = false;
		window.vtAction = (name) => applied.push(name);
		document.startViewTransition = vi.fn(fakeStart);
		vi.stubGlobal('matchMedia', (query) => ({
			matches: reducedMotion && query === '(prefers-reduced-motion: reduce)',
		}));
		global.Prado.Element._viewTransition = null;
	});

	afterEach(() => {
		delete window.vtAction;
		delete document.startViewTransition;
		vi.unstubAllGlobals();
		CallbackRequestManager.sendGates.length = 0;
		global.Prado.Element._viewTransition = null;
	});

	it('names the response part', () => {
		expect(CallbackRequestManager.VIEWTRANSITION_HEADER).toBe('X-PRADO-VIEWTRANSITION');
	});

	it('applies the actions at once without the response part', () => {
		const onComplete = vi.fn(() => applied.push('complete'));
		respond(new CallbackRequest('Target', { onComplete }), ACTIONS);
		expect(document.startViewTransition).not.toHaveBeenCalled();
		expect(applied).toEqual(['first', 'second', 'complete']);
	});

	it('applies every action of the response in one transition, in order', async () => {
		const request = new CallbackRequest('Target', {});
		respond(request, ACTIONS + TRANSITION);
		expect(document.startViewTransition).toHaveBeenCalledTimes(1);
		expect(applied).toEqual([]);
		transitions[0].capture();
		expect(applied).toEqual(['first', 'second']);
	});

	it('runs onSuccess before and onComplete after the actions', async () => {
		const onSuccess = vi.fn(() => applied.push('success'));
		const onComplete = vi.fn(() => applied.push('complete'));
		respond(new CallbackRequest('Target', { onSuccess, onComplete }), ACTIONS + TRANSITION);
		expect(applied).toEqual(['success']);
		await settle();
		expect(onComplete).not.toHaveBeenCalled();
		transitions[0].capture();
		await settle();
		expect(applied).toEqual(['success', 'first', 'second', 'complete']);
		expect(onComplete).toHaveBeenCalledWith(expect.any(Object), 'success');
	});

	it('holds the next request until the actions ran', async () => {
		respond(new CallbackRequest('Target', {}), ACTIONS + TRANSITION);
		expect(CallbackRequestManager.sendGates).toHaveLength(1);
		const hold = CallbackRequestManager.collectSendGates({});
		expect(hold).toHaveLength(1);
		let released = false;
		hold[0].then(() => { released = true; });
		await settle();
		expect(released).toBe(false);
		transitions[0].capture();
		await settle();
		expect(released).toBe(true);
		expect(CallbackRequestManager.sendGates).toHaveLength(0);
	});

	it('applies the actions at once under prefers-reduced-motion: reduce', () => {
		reducedMotion = true;
		const onComplete = vi.fn(() => applied.push('complete'));
		respond(new CallbackRequest('Target', { onComplete }), ACTIONS + TRANSITION);
		expect(document.startViewTransition).not.toHaveBeenCalled();
		expect(CallbackRequestManager.sendGates).toHaveLength(0);
		expect(applied).toEqual(['first', 'second', 'complete']);
	});

	it('applies the actions at once without view transition support', () => {
		delete document.startViewTransition;
		respond(new CallbackRequest('Target', {}), ACTIONS + TRANSITION);
		expect(applied).toEqual(['first', 'second']);
	});

	it('reports an action error as a callback exception and releases the holds', async () => {
		window.vtAction = (name) => {
			if (name === 'first')
				throw new Error('broken');
			applied.push(name);
		};
		const onException = vi.fn();
		const onComplete = vi.fn();
		const savedLog = CallbackRequestManager.LOG_ERROR;
		CallbackRequestManager.LOG_ERROR = false;
		try {
			respond(new CallbackRequest('Target', { onException, onComplete }), ACTIONS + TRANSITION);
			transitions[0].capture();
			await settle();
		} finally {
			CallbackRequestManager.LOG_ERROR = savedLog;
		}
		expect(onException).toHaveBeenCalledWith(expect.any(Object), expect.objectContaining({ message: 'broken' }));
		expect(applied).toEqual(['second']);
		expect(onComplete).toHaveBeenCalledTimes(1);
		expect(CallbackRequestManager.sendGates).toHaveLength(0);
	});

	it('reports an error thrown outside the actions and releases the holds', async () => {
		const onException = vi.fn();
		const request = new CallbackRequest('Target', { onException });
		const savedLog = CallbackRequestManager.LOG_ERROR;
		CallbackRequestManager.LOG_ERROR = false;
		const error = new Error('dispatch');
		try {
			request.applyInViewTransition(() => { throw error; });
			transitions[0].capture();
			await settle();
		} finally {
			CallbackRequestManager.LOG_ERROR = savedLog;
		}
		expect(onException).toHaveBeenCalledWith(request, error);
		expect(CallbackRequestManager.sendGates).toHaveLength(0);
		expect(request.actionsApplied).toBeNull();
	});
});
