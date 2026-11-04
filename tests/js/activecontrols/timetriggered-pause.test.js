/**
 * Tests for the pause of Prado.WebUI.TTimeTriggeredCallback while the page
 * is hidden or the browser is offline, and its resume.
 *
 * Source: framework/Web/Javascripts/source/prado/activecontrols/activecontrols3.js
 */

import { Registry, TTimeTriggeredCallback } from '../adapters/activecontrols.js';

describe('TTimeTriggeredCallback pause and resume', () => {
	let div, dispatch, visibility, online, original;

	function setVisibility(state) {
		visibility = state;
		document.dispatchEvent(new Event('visibilitychange'));
	}

	function setOnline(state) {
		online = state;
		window.dispatchEvent(new Event(state ? 'online' : 'offline'));
	}

	function timer(options) {
		return new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1, ...(options || {}) });
	}

	beforeEach(() => {
		for (const key of Object.keys(Registry))
			delete Registry[key];
		for (const key of Object.keys(TTimeTriggeredCallback.timers))
			delete TTimeTriggeredCallback.timers[key];
		div = document.createElement('div');
		div.id = 'ttc1';
		document.body.appendChild(div);
		vi.useFakeTimers();
		vi.setSystemTime(new Date('2026-01-01T00:00:00Z'));
		visibility = 'visible';
		online = true;
		Object.defineProperty(document, 'visibilityState', { configurable: true, get: () => visibility });
		Object.defineProperty(navigator, 'onLine', { configurable: true, get: () => online });
		dispatch = vi.fn();
		original = global.Prado.CallbackRequest;
		global.Prado.CallbackRequest = vi.fn(function () {
			return { dispatch };
		});
	});

	afterEach(() => {
		global.Prado.CallbackRequest = original;
		delete document.visibilityState;
		delete navigator.onLine;
		vi.useRealTimers();
		div.remove();
	});

	it('defaults PauseWhenHidden to true', () => {
		expect(timer().options.PauseWhenHidden).toBe(true);
		expect(timer({ PauseWhenHidden: false }).options.PauseWhenHidden).toBe(false);
	});

	it('sends a callback every interval while visible and online', () => {
		const ctrl = timer();
		ctrl.startTimer();
		vi.advanceTimersByTime(3000);
		expect(dispatch).toHaveBeenCalledTimes(3);
		ctrl.stopTimer();
	});

	it('stops the interval while hidden and resumes with a callback when due', () => {
		const ctrl = timer();
		ctrl.startTimer();
		vi.advanceTimersByTime(1000);
		expect(dispatch).toHaveBeenCalledTimes(1);
		setVisibility('hidden');
		expect(ctrl.timer).toBeNull();
		expect(ctrl.isPaused()).toBe(true);
		vi.advanceTimersByTime(5000);
		expect(dispatch).toHaveBeenCalledTimes(1);
		setVisibility('visible');
		expect(dispatch).toHaveBeenCalledTimes(2);
		expect(ctrl.timer).not.toBeNull();
		vi.advanceTimersByTime(1000);
		expect(dispatch).toHaveBeenCalledTimes(3);
		ctrl.stopTimer();
	});

	it('resumes without a callback when no interval passed', () => {
		const ctrl = timer();
		ctrl.startTimer();
		vi.advanceTimersByTime(1000);
		setVisibility('hidden');
		vi.advanceTimersByTime(200);
		setVisibility('visible');
		expect(dispatch).toHaveBeenCalledTimes(1);
		vi.advanceTimersByTime(1000);
		expect(dispatch).toHaveBeenCalledTimes(2);
		ctrl.stopTimer();
	});

	it('a timer started while hidden waits, then sends a callback when visible', () => {
		visibility = 'hidden';
		const ctrl = timer();
		ctrl.startTimer();
		expect(ctrl.timer).toBeUndefined();
		vi.advanceTimersByTime(3000);
		expect(dispatch).not.toHaveBeenCalled();
		setVisibility('visible');
		expect(dispatch).toHaveBeenCalledTimes(1);
		vi.advanceTimersByTime(1000);
		expect(dispatch).toHaveBeenCalledTimes(2);
		ctrl.stopTimer();
	});

	it('PauseWhenHidden false keeps the interval while hidden', () => {
		const ctrl = timer({ PauseWhenHidden: false });
		ctrl.startTimer();
		setVisibility('hidden');
		expect(ctrl.isPaused()).toBe(false);
		vi.advanceTimersByTime(2000);
		expect(dispatch).toHaveBeenCalledTimes(2);
		setVisibility('visible');
		expect(dispatch).toHaveBeenCalledTimes(2);
		ctrl.stopTimer();
	});

	it('stops the interval while offline and resumes when online', () => {
		const ctrl = timer();
		ctrl.startTimer();
		setOnline(false);
		expect(ctrl.timer).toBeNull();
		vi.advanceTimersByTime(3000);
		expect(dispatch).not.toHaveBeenCalled();
		setOnline(true);
		expect(dispatch).toHaveBeenCalledTimes(1);
		vi.advanceTimersByTime(1000);
		expect(dispatch).toHaveBeenCalledTimes(2);
		ctrl.stopTimer();
	});

	it('a tick while offline sends nothing', () => {
		const ctrl = timer();
		ctrl.startTimer();
		online = false;
		vi.advanceTimersByTime(1000);
		expect(dispatch).not.toHaveBeenCalled();
		ctrl.stopTimer();
	});

	it('a visible page does not resume a stopped timer', () => {
		const ctrl = timer();
		ctrl.startTimer();
		ctrl.stopTimer();
		setVisibility('hidden');
		setVisibility('visible');
		expect(ctrl.timer).toBeNull();
		expect(dispatch).not.toHaveBeenCalled();
	});

	it('resetTimer restarts only a started timer', () => {
		const ctrl = timer();
		ctrl.resetTimer();
		expect(ctrl.timer).toBeUndefined();
		ctrl.startTimer();
		const first = ctrl.timer;
		ctrl.setTimerInterval(2);
		expect(ctrl.timer).not.toBe(first);
		vi.advanceTimersByTime(2000);
		expect(dispatch).toHaveBeenCalledTimes(1);
		ctrl.stopTimer();
	});

	it('deinitialize removes the document and window listeners', () => {
		const ctrl = timer();
		ctrl.startTimer();
		ctrl.deinitialize();
		setVisibility('hidden');
		setVisibility('visible');
		expect(dispatch).not.toHaveBeenCalled();
	});
});
