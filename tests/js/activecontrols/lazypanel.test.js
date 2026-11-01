/**
 * Tests for Prado.WebUI.TLazyPanel (activecontrols3.js), issue #1408.
 *
 * Prado.CallbackRequest is mocked; IntersectionObserver is stubbed per test,
 * since jsdom has none.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { Registry, TLazyPanel } from '../adapters/activecontrols.js';
import { clearRegistry, mockCallbackRequest, restoreMocks } from '../helpers/callbackMock.js';

/** Installs an IntersectionObserver stub that records its instances. */
function stubIntersectionObserver() {
	const observers = [];
	const Stub = vi.fn(function (callback, options) {
		this.callback = callback;
		this.options = options;
		this.observe = vi.fn();
		this.disconnect = vi.fn();
		observers.push(this);
	});
	vi.stubGlobal('IntersectionObserver', Stub);
	return observers;
}

describe('TLazyPanel', () => {
	let el;

	beforeEach(() => {
		clearRegistry();
		el = document.createElement('div');
		el.id = 'lazy1';
		el.setAttribute('aria-busy', 'true');
		document.body.appendChild(el);
	});

	afterEach(() => {
		restoreMocks();
		vi.unstubAllGlobals();
		el.remove();
	});

	it('Load dispatches the callback when it initializes', () => {
		const { dispatchMock } = mockCallbackRequest();
		new TLazyPanel({ ID: 'lazy1', EventTarget: 'lazy1', Trigger: 'Load' });
		expect(dispatchMock).toHaveBeenCalledTimes(1);
		expect(global.Prado.CallbackRequest).toHaveBeenCalledWith('lazy1', expect.objectContaining({ Trigger: 'Load' }));
	});

	it('Manual waits for Prado.WebUI.TLazyPanel.load()', () => {
		const { dispatchMock } = mockCallbackRequest();
		new TLazyPanel({ ID: 'lazy1', EventTarget: 'lazy1', Trigger: 'Manual' });
		expect(dispatchMock).not.toHaveBeenCalled();
		TLazyPanel.load('lazy1');
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('load() of an unknown ID does nothing', () => {
		expect(() => TLazyPanel.load('missing')).not.toThrow();
	});

	it('Visible observes the panel with the root margin and loads on intersection', () => {
		const observers = stubIntersectionObserver();
		const { dispatchMock } = mockCallbackRequest();
		new TLazyPanel({ ID: 'lazy1', EventTarget: 'lazy1', Trigger: 'Visible', RootMargin: '200px' });
		expect(observers).toHaveLength(1);
		expect(observers[0].options).toEqual({ rootMargin: '200px' });
		expect(observers[0].observe).toHaveBeenCalledWith(el);
		expect(dispatchMock).not.toHaveBeenCalled();

		observers[0].callback([{ isIntersecting: false }]);
		expect(dispatchMock).not.toHaveBeenCalled();

		observers[0].callback([{ isIntersecting: true }]);
		expect(dispatchMock).toHaveBeenCalledTimes(1);
		expect(observers[0].disconnect).toHaveBeenCalled();
	});

	it('Visible loads at once without IntersectionObserver', () => {
		vi.stubGlobal('IntersectionObserver', undefined);
		const { dispatchMock } = mockCallbackRequest();
		new TLazyPanel({ ID: 'lazy1', EventTarget: 'lazy1', Trigger: 'Visible' });
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('dispatches once while a request is in flight', () => {
		const { dispatchMock } = mockCallbackRequest();
		const panel = new TLazyPanel({ ID: 'lazy1', EventTarget: 'lazy1', Trigger: 'Load' });
		panel.load();
		TLazyPanel.load('lazy1');
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('a failure keeps the placeholder, marks it failed, runs the ClientSide hook, and allows a retry', () => {
		const { dispatchMock } = mockCallbackRequest();
		const userFailure = vi.fn();
		new TLazyPanel({ ID: 'lazy1', EventTarget: 'lazy1', Trigger: 'Load', onFailure: userFailure });
		const options = global.Prado.CallbackRequest.mock.calls[0][1];
		options.onFailure('request', 'error');
		expect(el.hasAttribute('aria-busy')).toBe(false);
		expect(el.getAttribute('data-prado-lazy')).toBe('failed');
		expect(userFailure).toHaveBeenCalledWith('request', 'error');

		TLazyPanel.load('lazy1');
		expect(dispatchMock).toHaveBeenCalledTimes(2);
		expect(el.getAttribute('aria-busy')).toBe('true');
		expect(el.hasAttribute('data-prado-lazy')).toBe(false);
	});

	it('a registered replacement disconnects the old observer', () => {
		const observers = stubIntersectionObserver();
		mockCallbackRequest();
		new TLazyPanel({ ID: 'lazy1', EventTarget: 'lazy1', Trigger: 'Visible' });
		new TLazyPanel({ ID: 'lazy1', EventTarget: 'lazy1', Trigger: 'Visible' });
		expect(observers[0].disconnect).toHaveBeenCalled();
		expect(Registry['lazy1'].observer).toBe(observers[1]);
	});
});
