/**
 * Tests for Rico.Color and Prado.WebUI.TColorPicker.
 * Source: framework/Web/Javascripts/source/prado/colorpicker/colorpicker.js
 *
 * The colorpicker script defines Rico itself (no separate Rico npm package).
 * jQuery is pre-loaded by the loadScript helper.
 *
 * ESM note: only tests/js/adapters/colorpicker.js changes on ESM conversion;
 * this file stays unchanged.
 */

import { Rico, TColorPicker, Registry } from '../adapters/colorpicker.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────

/** Build a minimal DOM for TColorPicker and return {input, button, parent}. */
function buildPickerDOM(id) {
	const parent = document.createElement('div');
	parent.style.position = 'relative';

	const input = document.createElement('input');
	input.type = 'text';
	input.id = id;
	input.value = '#ffffff';

	const button = document.createElement('button');
	button.id = id + '_button';

	parent.appendChild(input);
	parent.appendChild(button);
	document.body.appendChild(parent);

	return { input, button, parent };
}

/** Remove any left-over picker DOM nodes and registry entries. */
function cleanupPicker(id) {
	const el = document.getElementById(id);
	if (el && el.parentNode) {
		el.parentNode.parentNode && el.parentNode.parentNode.removeChild(el.parentNode);
	}
	delete Registry[id];
}

// ─── Rico.Color constructor ───────────────────────────────────────────────────

describe('Rico.Color constructor', () => {
	it('stores r/g/b components', () => {
		const c = new Rico.Color(10, 20, 30);
		expect(c.rgb).toEqual({ r: 10, g: 20, b: 30 });
	});

	it('allows zero values', () => {
		const c = new Rico.Color(0, 0, 0);
		expect(c.rgb).toEqual({ r: 0, g: 0, b: 0 });
	});

	it('allows maximum values', () => {
		const c = new Rico.Color(255, 255, 255);
		expect(c.rgb).toEqual({ r: 255, g: 255, b: 255 });
	});
});

// ─── Rico.Color setters ───────────────────────────────────────────────────────

describe('Rico.Color.setRed / setGreen / setBlue', () => {
	it('setRed updates the red channel', () => {
		const c = new Rico.Color(0, 128, 64);
		c.setRed(200);
		expect(c.rgb.r).toBe(200);
		expect(c.rgb.g).toBe(128);
		expect(c.rgb.b).toBe(64);
	});

	it('setGreen updates the green channel', () => {
		const c = new Rico.Color(100, 0, 64);
		c.setGreen(200);
		expect(c.rgb.g).toBe(200);
	});

	it('setBlue updates the blue channel', () => {
		const c = new Rico.Color(100, 128, 0);
		c.setBlue(200);
		expect(c.rgb.b).toBe(200);
	});
});

// ─── Rico.Color.RGBtoHSB ─────────────────────────────────────────────────────

describe('Rico.Color.RGBtoHSB', () => {
	it('converts pure red to HSB', () => {
		const hsb = Rico.Color.RGBtoHSB(255, 0, 0);
		expect(hsb.h).toBeCloseTo(0, 5);
		expect(hsb.s).toBeCloseTo(1, 5);
		expect(hsb.b).toBeCloseTo(1, 5);
	});

	it('converts pure green to HSB', () => {
		const hsb = Rico.Color.RGBtoHSB(0, 255, 0);
		expect(hsb.s).toBeCloseTo(1, 5);
		expect(hsb.b).toBeCloseTo(1, 5);
	});

	it('converts pure blue to HSB', () => {
		const hsb = Rico.Color.RGBtoHSB(0, 0, 255);
		expect(hsb.s).toBeCloseTo(1, 5);
		expect(hsb.b).toBeCloseTo(1, 5);
	});

	it('converts white to brightness 1, saturation 0', () => {
		const hsb = Rico.Color.RGBtoHSB(255, 255, 255);
		expect(hsb.s).toBe(0);
		expect(hsb.b).toBeCloseTo(1, 5);
	});

	it('converts black to brightness 0', () => {
		const hsb = Rico.Color.RGBtoHSB(0, 0, 0);
		expect(hsb.b).toBe(0);
	});

	it('converts grey to zero saturation', () => {
		const hsb = Rico.Color.RGBtoHSB(128, 128, 128);
		expect(hsb.s).toBe(0);
	});
});

// ─── Rico.Color.HSBtoRGB ─────────────────────────────────────────────────────

describe('Rico.Color.HSBtoRGB', () => {
	it('converts hue 0 (red), full sat/bright to red', () => {
		const rgb = Rico.Color.HSBtoRGB(0, 1, 1);
		expect(rgb.r).toBe(255);
		expect(rgb.g).toBe(0);
		expect(rgb.b).toBe(0);
	});

	it('converts zero saturation to grey', () => {
		const rgb = Rico.Color.HSBtoRGB(0.5, 0, 0.5);
		expect(rgb.r).toBe(rgb.g);
		expect(rgb.g).toBe(rgb.b);
	});

	it('converts zero brightness to black regardless of hue', () => {
		const rgb = Rico.Color.HSBtoRGB(0.3, 1, 0);
		expect(rgb.r).toBe(0);
		expect(rgb.g).toBe(0);
		expect(rgb.b).toBe(0);
	});

	it('roundtrips through RGBtoHSB', () => {
		const original = { r: 123, g: 200, b: 45 };
		const hsb = Rico.Color.RGBtoHSB(original.r, original.g, original.b);
		const rgb = Rico.Color.HSBtoRGB(hsb.h, hsb.s, hsb.b);
		// Allow ±1 for integer rounding
		expect(Math.abs(rgb.r - original.r)).toBeLessThanOrEqual(1);
		expect(Math.abs(rgb.g - original.g)).toBeLessThanOrEqual(1);
		expect(Math.abs(rgb.b - original.b)).toBeLessThanOrEqual(1);
	});
});

// ─── Rico.Color HSB property setters (round-trip through HSB) ────────────────

describe('Rico.Color.setHue / setSaturation / setBrightness', () => {
	it('setHue changes only the hue component', () => {
		const c = new Rico.Color(255, 0, 0); // red, hue≈0
		c.setHue(0.5);                        // cyan
		const hsb = c.asHSB();
		expect(hsb.h).toBeCloseTo(0.5, 2);
	});

	it('setSaturation changes only the saturation component', () => {
		const c = new Rico.Color(255, 0, 0);
		c.setSaturation(0.5);
		const hsb = c.asHSB();
		expect(hsb.s).toBeCloseTo(0.5, 2);
	});

	it('setBrightness changes only the brightness component', () => {
		const c = new Rico.Color(255, 0, 0);
		c.setBrightness(0.5);
		const hsb = c.asHSB();
		expect(hsb.b).toBeCloseTo(0.5, 2);
	});
});

// ─── Rico.Color.darken / brighten ────────────────────────────────────────────

describe('Rico.Color.darken / brighten', () => {
	it('darken reduces brightness and does not go below 0', () => {
		const c = new Rico.Color(255, 255, 255);
		c.darken(0.3);
		const hsb = c.asHSB();
		expect(hsb.b).toBeCloseTo(0.7, 2);
	});

	it('darken clamps brightness to 0', () => {
		const c = new Rico.Color(128, 128, 128);
		c.darken(1);
		expect(c.rgb.r).toBe(0);
		expect(c.rgb.g).toBe(0);
		expect(c.rgb.b).toBe(0);
	});

	it('brighten increases brightness and does not exceed 1', () => {
		const c = new Rico.Color(0, 0, 128);
		c.brighten(1);
		const hsb = c.asHSB();
		expect(hsb.b).toBeCloseTo(1, 2);
	});
});

// ─── Rico.Color.blend ────────────────────────────────────────────────────────

describe('Rico.Color.blend', () => {
	it('blends two colours by averaging each channel', () => {
		const c1 = new Rico.Color(100, 200, 50);
		const c2 = new Rico.Color(50, 100, 150);
		c1.blend(c2);
		expect(c1.rgb.r).toBe(75);
		expect(c1.rgb.g).toBe(150);
		expect(c1.rgb.b).toBe(100);
	});

	it('blending black and white yields grey', () => {
		const c1 = new Rico.Color(0, 0, 0);
		const c2 = new Rico.Color(255, 255, 255);
		c1.blend(c2);
		// floor((0+255)/2) = 127
		expect(c1.rgb.r).toBe(127);
	});
});

// ─── Rico.Color.isBright / isDark ────────────────────────────────────────────

describe('Rico.Color.isBright / isDark', () => {
	it('white is bright', () => {
		expect(new Rico.Color(255, 255, 255).isBright()).toBe(true);
	});

	it('black is dark', () => {
		expect(new Rico.Color(0, 0, 0).isDark()).toBe(true);
	});

	it('bright and dark are mutually exclusive', () => {
		const c = new Rico.Color(200, 150, 100);
		expect(c.isBright()).toBe(!c.isDark());
	});
});

// ─── Rico.Color.asRGB / asHex / toString ─────────────────────────────────────

describe('Rico.Color string representations', () => {
	it('asRGB returns css rgb() string', () => {
		expect(new Rico.Color(10, 20, 30).asRGB()).toBe('rgb(10,20,30)');
	});

	it('asHex returns lowercase #rrggbb', () => {
		expect(new Rico.Color(255, 0, 0).asHex()).toBe('#ff0000');
	});

	it('asHex zero-pads single-digit hex parts', () => {
		expect(new Rico.Color(1, 2, 3).asHex()).toBe('#010203');
	});

	it('toString() equals asHex()', () => {
		const c = new Rico.Color(100, 150, 200);
		expect(c.toString()).toBe(c.asHex());
	});
});

// ─── Rico.Color.toColorPart ───────────────────────────────────────────────────

describe('Rico.Color.toColorPart', () => {
	it('converts 0 to "00"', () => {
		expect(new Rico.Color(0, 0, 0).toColorPart(0)).toBe('00');
	});

	it('converts 255 to "ff"', () => {
		expect(new Rico.Color(0, 0, 0).toColorPart(255)).toBe('ff');
	});

	it('clamps values above 255', () => {
		expect(new Rico.Color(0, 0, 0).toColorPart(300)).toBe('ff');
	});

	it('clamps values below 0', () => {
		expect(new Rico.Color(0, 0, 0).toColorPart(-5)).toBe('00');
	});
});

// ─── Rico.Color.createFromHex ─────────────────────────────────────────────────

describe('Rico.Color.createFromHex', () => {
	it('parses a 6-digit hex string with leading #', () => {
		const c = Rico.Color.createFromHex('#ff0000');
		expect(c.rgb).toEqual({ r: 255, g: 0, b: 0 });
	});

	it('parses a 6-digit hex string without leading #', () => {
		const c = Rico.Color.createFromHex('00ff00');
		expect(c.rgb).toEqual({ r: 0, g: 255, b: 0 });
	});

	it('parses a 3-digit shorthand hex', () => {
		const c = Rico.Color.createFromHex('f00');
		expect(c.rgb).toEqual({ r: 255, g: 0, b: 0 });
	});

	it('returns white for an empty hex code', () => {
		const c = Rico.Color.createFromHex('');
		expect(c.rgb).toEqual({ r: 255, g: 255, b: 255 });
	});

	it('parses mixed-case hex', () => {
		const c = Rico.Color.createFromHex('#FF8800');
		expect(c.rgb.r).toBe(255);
		expect(c.rgb.g).toBe(136);
		expect(c.rgb.b).toBe(0);
	});
});

// ─── Rico.Color.createColorFromBackground ────────────────────────────────────

describe('Rico.Color.createColorFromBackground', () => {
	afterEach(() => {
		document.body.innerHTML = '';
	});

	it('reads an rgb() background-color from a DOM element', () => {
		const div = document.createElement('div');
		div.style.backgroundColor = 'rgb(10, 20, 30)';
		document.body.appendChild(div);
		const c = Rico.Color.createColorFromBackground(div);
		expect(c.rgb.r).toBe(10);
		expect(c.rgb.g).toBe(20);
		expect(c.rgb.b).toBe(30);
	});

	it('returns white when background-color is null', () => {
		// jsdom returns '' for computed background-color on bare elements;
		// the source treats null as white.
		const div = document.createElement('div');
		// Force null by overriding jQuery's css() to return null
		const orig = global.jQuery.fn.css;
		global.jQuery.fn.css = function() { return null; };
		const c = Rico.Color.createColorFromBackground(div);
		global.jQuery.fn.css = orig;
		expect(c.rgb).toEqual({ r: 255, g: 255, b: 255 });
	});
});

// ─── TColorPicker static data ─────────────────────────────────────────────────

describe('TColorPicker static data', () => {
	it('has a Small palette with 7 rows', () => {
		expect(TColorPicker.palettes.Small.length).toBe(7);
	});

	it('Small palette rows contain 10 colour strings each', () => {
		TColorPicker.palettes.Small.forEach(row => {
			expect(row.length).toBe(10);
		});
	});

	it('has a Tiny palette with 3 rows', () => {
		expect(TColorPicker.palettes.Tiny.length).toBe(3);
	});

	it('UIImages contains a button.gif key', () => {
		expect(TColorPicker.UIImages['button.gif']).toBeDefined();
	});
});

// ─── TColorPicker.truncate ────────────────────────────────────────────────────

describe('TColorPicker.prototype.truncate', () => {
	let picker;
	const ID = 'trunc-test';

	beforeEach(() => {
		buildPickerDOM(ID);
		picker = new TColorPicker({ ID, ShowColorPicker: false });
	});

	afterEach(() => {
		cleanupPicker(ID);
		document.body.innerHTML = '';
	});

	it('returns value when within range', () => {
		expect(picker.truncate(128, 0, 255)).toBe(128);
	});

	it('clamps to min', () => {
		expect(picker.truncate(-5, 0, 255)).toBe(0);
	});

	it('clamps to max', () => {
		expect(picker.truncate(300, 0, 255)).toBe(255);
	});

	it('parses string values', () => {
		expect(picker.truncate('200', 0, 255)).toBe(200);
	});
});

// ─── TColorPicker initialisation ─────────────────────────────────────────────

describe('TColorPicker initialisation', () => {
	const ID = 'cp-init-test';

	beforeEach(() => {
		buildPickerDOM(ID);
	});

	afterEach(() => {
		cleanupPicker(ID);
		document.body.innerHTML = '';
	});

	it('registers itself in Prado.Registry', () => {
		new TColorPicker({ ID, ShowColorPicker: false });
		expect(Registry[ID]).toBeDefined();
	});

	it('stores a reference to the input element', () => {
		const picker = new TColorPicker({ ID, ShowColorPicker: false });
		expect(picker.input).toBe(document.getElementById(ID));
	});

	it('stores a reference to the button element', () => {
		const picker = new TColorPicker({ ID, ShowColorPicker: false });
		expect(picker.button).toBe(document.getElementById(ID + '_button'));
	});

	it('starts with showing = false', () => {
		const picker = new TColorPicker({ ID, ShowColorPicker: false });
		expect(picker.showing).toBe(false);
	});

	it('starts with element = null (picker panel not yet created)', () => {
		const picker = new TColorPicker({ ID, ShowColorPicker: false });
		expect(picker.element).toBeNull();
	});

	it('applies default options when none supplied', () => {
		const picker = new TColorPicker({ ID, ShowColorPicker: false });
		expect(picker.options.Mode).toBe('Basic');
		expect(picker.options.Palette).toBe('Small');
	});

	it('merges caller options over defaults', () => {
		const picker = new TColorPicker({ ID, ShowColorPicker: false, Palette: 'Tiny', Mode: 'Full' });
		expect(picker.options.Palette).toBe('Tiny');
		expect(picker.options.Mode).toBe('Full');
	});
});

// ─── TColorPicker.updatePicker ────────────────────────────────────────────────

describe('TColorPicker.updatePicker', () => {
	const ID = 'cp-update-test';
	let picker;

	beforeEach(() => {
		buildPickerDOM(ID);
		picker = new TColorPicker({ ID, ShowColorPicker: false });
	});

	afterEach(() => {
		cleanupPicker(ID);
		document.body.innerHTML = '';
	});

	it('sets the button background colour from the input value', () => {
		picker.input.value = '#ff0000';
		picker.updatePicker();
		// jsdom sets backgroundColor as 'rgb(255, 0, 0)' or '#ff0000'
		const bg = picker.button.style.backgroundColor;
		expect(bg).toBeTruthy();
	});
});

// ─── TColorPicker.updateColor ─────────────────────────────────────────────────

describe('TColorPicker.updateColor', () => {
	const ID = 'cp-updatecolor-test';
	let picker;

	beforeEach(() => {
		buildPickerDOM(ID);
		picker = new TColorPicker({ ID, ShowColorPicker: false });
	});

	afterEach(() => {
		cleanupPicker(ID);
		document.body.innerHTML = '';
	});

	it('sets the input value to the hex colour in uppercase', () => {
		const color = new Rico.Color(255, 0, 0);
		picker.updateColor(color);
		expect(picker.input.value).toBe('#FF0000');
	});

	it('calls OnColorSelected callback if provided', () => {
		const spy = vi.fn();
		picker.options.OnColorSelected = spy;
		const color = new Rico.Color(0, 128, 0);
		picker.updateColor(color);
		expect(spy).toHaveBeenCalledWith(picker, color);
	});

	it('calls onChange if it is a function', () => {
		const spy = vi.fn();
		picker.onChange = spy;
		const color = new Rico.Color(0, 0, 255);
		picker.updateColor(color);
		expect(spy).toHaveBeenCalledWith(color);
	});
});

// ─── TColorPicker.getBasicPickerContainer ─────────────────────────────────────

describe('TColorPicker.getBasicPickerContainer', () => {
	const ID = 'cp-basic-test';
	let picker;

	beforeEach(() => {
		buildPickerDOM(ID);
		picker = new TColorPicker({ ID, ShowColorPicker: false });
	});

	afterEach(() => {
		cleanupPicker(ID);
		document.body.innerHTML = '';
	});

	it('returns a div with correct id', () => {
		const div = picker.getBasicPickerContainer(ID, 'Small');
		expect(div.id).toBe(ID + '_picker');
	});

	it('contains a table with class including "Small"', () => {
		const div = picker.getBasicPickerContainer(ID, 'Small');
		const table = div.querySelector('table');
		expect(table.className).toContain('Small');
	});

	it('renders all colour cells for the Small palette', () => {
		const div = picker.getBasicPickerContainer(ID, 'Small');
		const imgs = div.querySelectorAll('img');
		// Small has 7 rows × 10 cols = 70 cells
		expect(imgs.length).toBe(70);
	});

	it('renders all colour cells for the Tiny palette', () => {
		const div = picker.getBasicPickerContainer(ID, 'Tiny');
		const imgs = div.querySelectorAll('img');
		// Tiny has 3 rows × 4 cols = 12 cells
		expect(imgs.length).toBe(12);
	});
});

// ─── TColorPicker.getFullPickerContainer ──────────────────────────────────────

describe('TColorPicker.getFullPickerContainer', () => {
	const ID = 'cp-full-test';
	let picker;

	beforeEach(() => {
		buildPickerDOM(ID);
		picker = new TColorPicker({ ID, ShowColorPicker: false });
	});

	afterEach(() => {
		cleanupPicker(ID);
		document.body.innerHTML = '';
	});

	it('returns a div with FullColorPicker class', () => {
		const div = picker.getFullPickerContainer(ID);
		expect(div.className).toContain('FullColorPicker');
	});

	it('creates H/S/V/R/G/B text inputs', () => {
		picker.getFullPickerContainer(ID);
		['H', 'S', 'V', 'R', 'G', 'B'].forEach(type => {
			expect(picker.inputs[type]).toBeDefined();
			expect(picker.inputs[type].tagName.toLowerCase()).toBe('input');
		});
	});

	it('creates a HEX text input with maxlength 6', () => {
		picker.getFullPickerContainer(ID);
		expect(picker.inputs['HEX']).toBeDefined();
		expect(picker.inputs['HEX'].maxlength).toBe('6');
	});

	it('creates OK and Cancel buttons', () => {
		picker.getFullPickerContainer(ID);
		expect(picker.buttons.OK.value).toBe(picker.options.OKButtonText);
		expect(picker.buttons.Cancel.value).toBe(picker.options.CancelButtonText);
	});
});

// ─── TColorPicker.setColor ────────────────────────────────────────────────────

describe('TColorPicker.setColor', () => {
	const ID = 'cp-setcolor-test';
	let picker;

	beforeEach(() => {
		buildPickerDOM(ID);
		picker = new TColorPicker({ ID, ShowColorPicker: false });
		// We need the full picker inputs to exist
		picker.getFullPickerContainer(ID);
	});

	afterEach(() => {
		cleanupPicker(ID);
		document.body.innerHTML = '';
	});

	it('fills in H/S/V/R/G/B/HEX inputs from the given colour', () => {
		const color = new Rico.Color(255, 0, 0); // pure red
		picker.setColor(color, false);
		expect(picker.inputs.R.value).toBe('255');
		expect(picker.inputs.G.value).toBe('0');
		expect(picker.inputs.B.value).toBe('0');
		expect(picker.inputs.HEX.value).toBe('FF0000');
	});

	it('HEX value is always uppercase', () => {
		const color = new Rico.Color(171, 205, 239); // #ABCDEF
		picker.setColor(color, false);
		expect(picker.inputs.HEX.value).toMatch(/^[0-9A-F]{6}$/);
	});
});

// ─── TColorPicker.hide / show / keyPressed ────────────────────────────────────

describe('TColorPicker.hide', () => {
	const ID = 'cp-hide-test';
	let picker;

	beforeEach(() => {
		buildPickerDOM(ID);
		picker = new TColorPicker({ ID, ShowColorPicker: false });
		// Create the picker panel
		picker.element = picker.getBasicPickerContainer(ID, 'Small');
		picker.input.parentNode.appendChild(picker.element);
		picker.element.style.display = 'block';
		picker.showing = true;
		// attach stub event listeners so hide() can call stopObserving
		picker._documentClickEvent = () => {};
		picker._documentKeyDownEvent = () => {};
	});

	afterEach(() => {
		cleanupPicker(ID);
		document.body.innerHTML = '';
	});

	it('sets display to none', () => {
		picker.hide({});
		expect(picker.element.style.display).toBe('none');
	});

	it('sets showing to false', () => {
		picker.hide({});
		expect(picker.showing).toBe(false);
	});

	it('is a no-op when already hidden', () => {
		picker.showing = false;
		picker.hide({});
		// element display unchanged from whatever it was set to outside hide()
		expect(picker.showing).toBe(false);
	});
});

describe('TColorPicker.keyPressed', () => {
	const ID = 'cp-key-test';
	let picker;

	beforeEach(() => {
		buildPickerDOM(ID);
		picker = new TColorPicker({ ID, ShowColorPicker: false });
		picker.element = picker.getBasicPickerContainer(ID, 'Small');
		picker.input.parentNode.appendChild(picker.element);
		picker.element.style.display = 'block';
		picker.showing = true;
		picker._documentClickEvent = () => {};
		picker._documentKeyDownEvent = () => {};
	});

	afterEach(() => {
		cleanupPicker(ID);
		document.body.innerHTML = '';
	});

	it('hides the picker when Escape (keyCode 27) is pressed', () => {
		picker.keyPressed({ keyCode: 27 }, 'Basic');
		expect(picker.showing).toBe(false);
	});

	it('does not hide for other key codes', () => {
		picker.keyPressed({ keyCode: 13 }, 'Basic');
		expect(picker.showing).toBe(true);
	});
});

// ─── TColorPicker.hideOnClick ─────────────────────────────────────────────────

describe('TColorPicker.hideOnClick', () => {
	const ID = 'cp-hideclick-test';
	let picker;

	beforeEach(() => {
		buildPickerDOM(ID);
		picker = new TColorPicker({ ID, ShowColorPicker: false });
		picker.element = picker.getBasicPickerContainer(ID, 'Small');
		picker.input.parentNode.appendChild(picker.element);
		picker.element.style.display = 'block';
		picker.showing = true;
		picker._documentClickEvent = () => {};
		picker._documentKeyDownEvent = () => {};
	});

	afterEach(() => {
		cleanupPicker(ID);
		document.body.innerHTML = '';
	});

	it('hides when clicked outside the picker elements', () => {
		const outside = document.createElement('div');
		outside.className = 'outside';
		document.body.appendChild(outside);
		picker.hideOnClick('Basic', { target: outside });
		expect(picker.showing).toBe(false);
	});

	it('does not hide when the target is the input element', () => {
		picker.hideOnClick('Basic', { target: picker.input });
		expect(picker.showing).toBe(true);
	});

	it('does not hide when the target is the button element', () => {
		picker.hideOnClick('Basic', { target: picker.button });
		expect(picker.showing).toBe(true);
	});

	it('does not hide when clicking the basic picker container itself', () => {
		// element class is "TColorPicker BasicColorPicker" — contains "ColorPicker"
		picker.hideOnClick('Basic', { target: picker.element });
		expect(picker.showing).toBe(true);
	});

	it('does not hide when clicking a child node inside the basic picker', () => {
		// Walk up from a deep child — "ColorPicker" must match on an ancestor
		const child = picker.element.querySelector('img') ?? picker.element.firstElementChild;
		picker.hideOnClick('Basic', { target: child });
		expect(picker.showing).toBe(true);
	});

	it('does not hide when clicking inside a Simple-mode (Tiny palette) picker', () => {
		// PHP Simple mode sends Palette:"Tiny"; JS still uses getBasicPickerContainer
		// and the container still has class "... BasicColorPicker"
		const simpleEl = picker.getBasicPickerContainer(ID, 'Tiny');
		picker.input.parentNode.appendChild(simpleEl);
		const child = simpleEl.querySelector('img') ?? simpleEl.firstElementChild;
		picker.hideOnClick('Basic', { target: child });
		expect(picker.showing).toBe(true);
		simpleEl.remove();
	});

	it('does not hide when clicking inside a Full picker container', () => {
		const fullEl = picker.getFullPickerContainer(ID);
		picker.input.parentNode.appendChild(fullEl);
		// element class contains "FullColorPicker"
		picker.hideOnClick('Full', { target: fullEl });
		expect(picker.showing).toBe(true);
		fullEl.remove();
	});

	it('is a no-op when not showing', () => {
		picker.showing = false;
		const outside = document.createElement('div');
		picker.hideOnClick('Basic', { target: outside });
		// just ensure no error thrown
		expect(picker.showing).toBe(false);
	});
});
