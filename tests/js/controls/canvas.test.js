/**
 * Tests for Prado.WebUI.TCanvas (canvas.js).
 * Source: framework/Web/Javascripts/source/prado/controls/canvas.js
 *
 * Strategy
 * --------
 * jsdom has no canvas rendering, layout, or ResizeObserver. Each test stubs
 * HTMLCanvasElement.prototype.getContext with a recording context, the element's
 * getBoundingClientRect with a display size, and, where needed, a ResizeObserver
 * whose callback the test calls. The OnDraw handler records its parameters.
 *
 * ESM note: only tests/js/adapters/canvas.js changes on ESM conversion.
 */

import { TCanvas } from '../adapters/canvas.js';

const ID = 'cv1';

let observers = [];
let contextTypes = [];
const originalGetContext = HTMLCanvasElement.prototype.getContext;
const originalRatio = window.devicePixelRatio;

class StubResizeObserver {
	constructor(callback) {
		this.callback = callback;
		this.observed = [];
		this.disconnected = false;
		observers.push(this);
	}
	observe(el) { this.observed.push(el); }
	disconnect() { this.disconnected = true; }
}

function stubContext() {
	return { transforms: [], setTransform(...args) { this.transforms.push(args); } };
}

function makeCanvas(rect = { width: 200, height: 100 }) {
	const el = document.createElement('canvas');
	el.id = ID;
	el.getBoundingClientRect = () => ({ width: rect.width, height: rect.height });
	document.body.appendChild(el);
	return { el, rect };
}

function makeControl(extra = {}) {
	const draws = [];
	const control = new TCanvas(Object.assign({
		ID: ID,
		ContextType: '2d',
		MatchDisplaySize: true,
		onDraw: (sender, param) => draws.push({ sender, ...param }),
	}, extra));
	return { control, draws };
}

beforeEach(() => {
	observers = [];
	contextTypes = [];
	HTMLCanvasElement.prototype.getContext = function(type) {
		contextTypes.push(type);
		return this._ctx || (this._ctx = stubContext());
	};
	global.ResizeObserver = StubResizeObserver;
	window.devicePixelRatio = 2;
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
});

afterEach(() => {
	HTMLCanvasElement.prototype.getContext = originalGetContext;
	delete global.ResizeObserver;
	window.devicePixelRatio = originalRatio;
	document.body.innerHTML = '';
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
});

describe('TCanvas draw on init', () => {
	it('calls OnDraw with the context, CSS size, and pixel ratio', () => {
		const { el } = makeCanvas();
		const { control, draws } = makeControl();
		expect(draws).toHaveLength(1);
		expect(draws[0].sender).toBe(control);
		expect(draws[0].canvas).toBe(el);
		expect(draws[0].context).toBe(el._ctx);
		expect(draws[0].width).toBe(200);
		expect(draws[0].height).toBe(100);
		expect(draws[0].pixelRatio).toBe(2);
		expect(draws[0].reducedMotion).toBe(false);
	});

	it('sizes the bitmap to the display size times the pixel ratio and scales the context', () => {
		const { el } = makeCanvas();
		makeControl();
		expect(el.width).toBe(400);
		expect(el.height).toBe(200);
		expect(el._ctx.transforms).toEqual([[2, 0, 0, 2, 0, 0]]);
	});

	it('creates the context of ContextType once', () => {
		makeCanvas();
		const { control } = makeControl({ ContextType: 'webgl2' });
		control.lastSize = null;
		control.draw();
		expect(contextTypes).toEqual(['webgl2']);
	});

	it('does nothing without OnDraw', () => {
		const { el } = makeCanvas();
		makeControl({ onDraw: undefined });
		expect(contextTypes).toEqual([]);
		expect(el.width).toBe(300);
	});

	it('does nothing without the element', () => {
		const { draws } = makeControl();
		expect(draws).toHaveLength(0);
		expect(observers).toHaveLength(0);
	});
});

describe('TCanvas MatchDisplaySize', () => {
	it('uses the bitmap size and ratio 1 when false', () => {
		const { el } = makeCanvas();
		el.width = 50;
		el.height = 25;
		const { draws } = makeControl({ MatchDisplaySize: false });
		expect(draws[0].width).toBe(50);
		expect(draws[0].height).toBe(25);
		expect(draws[0].pixelRatio).toBe(1);
		expect(el.width).toBe(50);
		expect(observers).toHaveLength(0);
	});

	it('uses the bitmap size while the element is not displayed', () => {
		makeCanvas({ width: 0, height: 0 });
		const { draws } = makeControl();
		expect(draws[0].width).toBe(300);
		expect(draws[0].height).toBe(150);
		expect(draws[0].pixelRatio).toBe(1);
	});

	it('fixes a display size that follows the bitmap', () => {
		const el = document.createElement('canvas');
		el.id = ID;
		el.getBoundingClientRect = () => ({ width: el.width, height: el.height });
		document.body.appendChild(el);
		makeControl();
		expect(el.style.width).toBe('300px');
		expect(el.style.height).toBe('150px');
		expect(el.width).toBe(600);
	});

	it('leaves a CSS display size alone', () => {
		const { el } = makeCanvas();
		makeControl();
		expect(el.style.width).toBe('');
		expect(el.style.height).toBe('');
	});
});

describe('TCanvas resize', () => {
	it('redraws when the display size changes', () => {
		const { el, rect } = makeCanvas();
		const { draws } = makeControl();
		expect(observers[0].observed).toEqual([el]);
		rect.width = 300;
		observers[0].callback();
		expect(draws).toHaveLength(2);
		expect(draws[1].width).toBe(300);
		expect(el.width).toBe(600);
	});

	it('does not redraw when the size is unchanged', () => {
		makeCanvas();
		const { draws } = makeControl();
		observers[0].callback();
		expect(draws).toHaveLength(1);
	});

	it('redraws when the pixel ratio changes', () => {
		const { el } = makeCanvas();
		const { draws } = makeControl();
		window.devicePixelRatio = 3;
		observers[0].callback();
		expect(draws).toHaveLength(2);
		expect(el.width).toBe(600);
	});

	it('disconnects the observer when the control is replaced', () => {
		makeCanvas();
		makeControl();
		const { draws } = makeControl();
		expect(observers[0].disconnected).toBe(true);
		expect(draws).toHaveLength(1);
	});
});

describe('TCanvas reduced motion', () => {
	it('passes the reduced-motion preference', () => {
		window.matchMedia = (query) => ({ matches: query === '(prefers-reduced-motion: reduce)' });
		makeCanvas();
		const { draws } = makeControl();
		delete window.matchMedia;
		expect(draws[0].reducedMotion).toBe(true);
	});
});
