/**
 * Tests for Prado.Element.scrollTo: duration, offset, easing, a zero duration,
 * prefers-reduced-motion, and stopping a scroll in progress.
 *
 * requestAnimationFrame is replaced by a queue that each test flushes with
 * chosen frame timestamps.
 *
 * Source: framework/Web/Javascripts/source/prado/prado.js
 */

import { Element } from '../adapters/prado-core.js';

describe('Prado.Element.scrollTo', () => {
	let el, frames, nextFrameId, scrollSpy, reducedMotion;

	// Runs the queued frames once, each with the timestamp `now`.
	const frame = (now) => {
		const queued = frames;
		frames = new Map();
		queued.forEach((callback) => callback(now));
	};

	const lastTop = () => scrollSpy.mock.calls.at(-1)[0].top;

	beforeEach(() => {
		frames = new Map();
		nextFrameId = 1;
		reducedMotion = false;
		vi.stubGlobal('requestAnimationFrame', (callback) => {
			const id = nextFrameId++;
			frames.set(id, callback);
			return id;
		});
		vi.stubGlobal('cancelAnimationFrame', (id) => { frames.delete(id); });
		vi.stubGlobal('matchMedia', (query) => ({
			matches: reducedMotion && query === '(prefers-reduced-motion: reduce)',
		}));
		Object.defineProperty(window, 'pageXOffset', { value: 0, configurable: true });
		Object.defineProperty(window, 'pageYOffset', { value: 100, configurable: true });
		scrollSpy = vi.spyOn(window, 'scrollTo').mockImplementation(() => {});

		el = document.createElement('div');
		el.id = 'scroll-target';
		// 1000px below the current viewport top: document position 1100.
		el.getBoundingClientRect = () => ({ top: 1000 });
		document.body.appendChild(el);
		Element._scrollFrame = null;
	});

	afterEach(() => {
		el.remove();
		Element._scrollFrame = null;
		scrollSpy.mockRestore();
		vi.unstubAllGlobals();
		delete window.pageXOffset;
		delete window.pageYOffset;
	});

	it('does nothing when the element does not exist', () => {
		Element.scrollTo('no-such-element');
		expect(frames.size).toBe(0);
		expect(scrollSpy).not.toHaveBeenCalled();
	});

	it('scrolls over 500ms by default', () => {
		Element.scrollTo('scroll-target');
		expect(scrollSpy).not.toHaveBeenCalled();

		frame(1000);
		expect(lastTop()).toBe(100);
		frame(1250);
		expect(lastTop()).toBeCloseTo(575);
		expect(frames.size).toBe(1);
		frame(1500);
		expect(lastTop()).toBe(1050);
		expect(frames.size).toBe(0);
		expect(Element._scrollFrame).toBeNull();
	});

	it('lasts the given duration', () => {
		Element.scrollTo('scroll-target', { duration: 2000 });
		frame(0);
		frame(500);
		expect(frames.size).toBe(1);
		frame(1999);
		expect(frames.size).toBe(1);
		frame(2000);
		expect(lastTop()).toBe(1050);
		expect(frames.size).toBe(0);
	});

	it('eases in and out', () => {
		Element.scrollTo('scroll-target', { duration: 1000, offset: 0 });
		frame(0);
		frame(250);
		// swing easing: 0.5 - cos(0.25 * PI) / 2
		expect(lastTop()).toBeCloseTo(100 + 1000 * (0.5 - Math.cos(Math.PI / 4) / 2));
		expect(lastTop() - 100).toBeLessThan(250);
	});

	it('stops at the offset above the element', () => {
		Element.scrollTo('scroll-target', { offset: 200, duration: 100 });
		frame(0);
		frame(100);
		expect(lastTop()).toBe(900);
	});

	it('keeps the horizontal position and scrolls instantly each frame', () => {
		Object.defineProperty(window, 'pageXOffset', { value: 30, configurable: true });
		Element.scrollTo('scroll-target', { duration: 100 });
		frame(0);
		expect(scrollSpy).toHaveBeenLastCalledWith({ left: 30, top: 100, behavior: 'instant' });
	});

	it('jumps at once with a duration of 0', () => {
		Element.scrollTo('scroll-target', { duration: 0 });
		expect(scrollSpy).toHaveBeenCalledTimes(1);
		expect(scrollSpy).toHaveBeenCalledWith({ left: 0, top: 1050, behavior: 'instant' });
		expect(frames.size).toBe(0);
	});

	it('jumps at once under prefers-reduced-motion: reduce', () => {
		reducedMotion = true;
		Element.scrollTo('scroll-target', { duration: 1000 });
		expect(scrollSpy).toHaveBeenCalledTimes(1);
		expect(scrollSpy).toHaveBeenCalledWith({ left: 0, top: 1050, behavior: 'instant' });
		expect(frames.size).toBe(0);
	});

	it('stops the scroll in progress when called again', () => {
		Element.scrollTo('scroll-target', { duration: 1000 });
		frame(0);
		expect(frames.size).toBe(1);

		Element.scrollTo('scroll-target', { duration: 0, offset: 0 });
		expect(frames.size).toBe(0);
		expect(lastTop()).toBe(1100);
		expect(Element._scrollFrame).toBeNull();
	});
});
