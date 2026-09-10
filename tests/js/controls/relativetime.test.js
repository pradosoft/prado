/**
 * Tests for Prado.WebUI.TRelativeTime (relativetime.js).
 * Source: framework/Web/Javascripts/source/prado/controls/relativetime.js
 *
 * Strategy
 * --------
 * A `<time>` element is placed in the jsdom document, then `new TRelativeTime(options)`
 * constructs and registers the control, which renders once immediately. The rendered
 * innerHTML is asserted. Node provides Intl.PluralRules / Intl.NumberFormat /
 * Intl.RelativeTimeFormat, so localization is exercised for real.
 *
 * The redraw loop's pending setTimeout is cleared in afterEach.
 *
 * ESM note: only tests/js/adapters/relativetime.js changes on ESM conversion.
 */

import { TRelativeTime } from '../adapters/relativetime.js';

const ID = 'rt1';

const EN_LONG = {
	year: { one: '{0} year', other: '{0} years' },
	month: { one: '{0} month', other: '{0} months' },
	week: { one: '{0} week', other: '{0} weeks' },
	day: { one: '{0} day', other: '{0} days' },
	hour: { one: '{0} hour', other: '{0} hours' },
	minute: { one: '{0} minute', other: '{0} minutes' },
	second: { one: '{0} second', other: '{0} seconds' }
};

const EN_NARROW = {
	year: { one: '{0}y', other: '{0}y' },
	month: { one: '{0}mo', other: '{0}mo' },
	week: { one: '{0}w', other: '{0}w' },
	day: { one: '{0}d', other: '{0}d' },
	hour: { one: '{0}h', other: '{0}h' },
	minute: { one: '{0}m', other: '{0}m' },
	second: { one: '{0}s', other: '{0}s' }
};

let instances = [];

function nowSeconds() {
	return Math.floor(Date.now() / 1000);
}

function buildOptions(extra = {}) {
	return Object.assign({
		ID: ID,
		ServerTime: nowSeconds(),
		OriginTime: nowSeconds(),
		UseServerTime: false,
		Separator: ' ',
		DisplayZero: false,
		SignificantElements: 1,
		PartialElement: true,
		PartialCount: [2, 3, 2, 3, 3, 4, 5],
		ClickForDateTime: true,
		Culture: 'en-US',
		Mode: 'long',
		AbsoluteText: 'June 15, 2024',
		UnitPatterns: EN_LONG
	}, extra);
}

function makeControl(extra = {}) {
	const el = document.createElement('time');
	el.id = ID;
	document.body.appendChild(el);
	const control = new TRelativeTime(buildOptions(extra));
	instances.push(control);
	return { control, el };
}

beforeEach(() => {
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
});

afterEach(() => {
	for (const c of instances) {
		if (c && c.loopTimeout) {
			clearTimeout(c.loopTimeout);
		}
	}
	instances = [];
	document.body.innerHTML = '';
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
});

describe('TRelativeTime prototype', () => {
	it('has the expected methods', () => {
		expect(typeof TRelativeTime.prototype.onInit).toBe('function');
		expect(typeof TRelativeTime.prototype.render).toBe('function');
		expect(typeof TRelativeTime.prototype.localizeUnit).toBe('function');
		expect(typeof TRelativeTime.prototype.applyDirection).toBe('function');
	});
});

describe('TRelativeTime.localizeUnit', () => {
	it('selects singular and plural patterns via Intl.PluralRules', () => {
		const { control } = makeControl();
		expect(control.localizeUnit('minute', 1)).toBe('1 minute');
		expect(control.localizeUnit('minute', 5)).toBe('5 minutes');
	});
});

describe('TRelativeTime.render', () => {
	it('renders a single past unit with direction', () => {
		const { el } = makeControl({ OriginTime: nowSeconds() - 305 });
		expect(el.innerHTML).toBe('5 minutes ago');
	});

	it('renders a single future unit with direction', () => {
		const { el } = makeControl({ OriginTime: nowSeconds() + 305 });
		expect(el.innerHTML).toBe('in 5 minutes');
	});

	it('composes multiple units and keeps a single direction', () => {
		const { el } = makeControl({
			SignificantElements: 2,
			OriginTime: nowSeconds() - (3600 + 305)
		});
		expect(el.innerHTML).toBe('1 hour 5 minutes ago');
	});

	it('renders zero seconds without a direction', () => {
		const { el } = makeControl({ OriginTime: nowSeconds() });
		expect(el.innerHTML).toBe('0 seconds');
	});

	it('honors the separator between units', () => {
		const { el } = makeControl({
			SignificantElements: 2,
			Separator: ', ',
			OriginTime: nowSeconds() - (3600 + 305)
		});
		expect(el.innerHTML).toBe('1 hour, 5 minutes ago');
	});

	it('uses narrow patterns and narrow relative style', () => {
		const { el } = makeControl({
			Mode: 'narrow',
			UnitPatterns: EN_NARROW,
			OriginTime: nowSeconds() - 305
		});
		expect(el.innerHTML).toContain('5m');
	});
});

describe('TRelativeTime.render partial element', () => {
	it('adds the next smaller unit when the leading unit is at/below its threshold', () => {
		// hours = 2 ≤ HoursWithMinutes (PartialCount[4] = 3), PartialElement on.
		const { el } = makeControl({ OriginTime: nowSeconds() - (2 * 3600 + 305) });
		expect(el.innerHTML).toBe('2 hours 5 minutes ago');
	});

	it('shows only the leading unit when PartialElement is off', () => {
		const { el } = makeControl({
			PartialElement: false,
			OriginTime: nowSeconds() - (2 * 3600 + 305)
		});
		expect(el.innerHTML).toBe('2 hours ago');
	});
});

describe('TRelativeTime.render zero display', () => {
	it('includes a zero-valued unit when DisplayZero is on', () => {
		const { el } = makeControl({
			SignificantElements: 2,
			DisplayZero: true,
			OriginTime: nowSeconds() - 3600
		});
		expect(el.innerHTML).toBe('1 hour 0 minutes ago');
	});

	it('omits a zero-valued unit when DisplayZero is off', () => {
		const { el } = makeControl({
			SignificantElements: 2,
			DisplayZero: false,
			OriginTime: nowSeconds() - 3600
		});
		expect(el.innerHTML).toBe('1 hour ago');
	});
});

describe('TRelativeTime server time', () => {
	it('anchors the delta to ServerTime when UseServerTime is on', () => {
		const { el } = makeControl({
			UseServerTime: true,
			ServerTime: nowSeconds() + 600,
			OriginTime: nowSeconds()
		});
		expect(el.innerHTML).toBe('10 minutes ago');
	});

	it('ignores ServerTime when UseServerTime is off', () => {
		const { el } = makeControl({
			UseServerTime: false,
			ServerTime: nowSeconds() + 600,
			OriginTime: nowSeconds()
		});
		expect(el.innerHTML).toBe('0 seconds');
	});
});

describe('TRelativeTime single-unit inflection', () => {
	it('returns the RelativeTimeFormat output directly so inflected forms survive', () => {
		// Russian past-tense "1 минуту назад" (accusative) differs from the bare "1 минута".
		const { el } = makeControl({
			Culture: 'ru',
			PartialElement: false,
			UnitPatterns: Object.assign({}, EN_LONG, {
				minute: { one: '{0} минута', few: '{0} минуты', many: '{0} минут', other: '{0} минуты' }
			}),
			OriginTime: nowSeconds() - 75
		});
		expect(el.innerHTML).toBe('1 минуту назад');
	});
});

describe('TRelativeTime DurationOnly', () => {
	it('renders the bare magnitude without direction for past and future', () => {
		const past = makeControl({ DurationOnly: true, OriginTime: nowSeconds() - 305 });
		expect(past.el.innerHTML).toBe('5 minutes');
	});

	it('keeps multi-unit composition without direction', () => {
		const { el } = makeControl({
			DurationOnly: true,
			SignificantElements: 2,
			OriginTime: nowSeconds() - (3600 + 305)
		});
		expect(el.innerHTML).toBe('1 hour 5 minutes');
	});

	it('applies direction when DurationOnly is absent from the options', () => {
		const { el } = makeControl({ OriginTime: nowSeconds() + 305 });
		expect(el.innerHTML).toBe('in 5 minutes');
	});
});

describe('TRelativeTime Intl fallbacks', () => {
	it('renders the bare magnitude when RelativeTimeFormat is unavailable', () => {
		const { control, el } = makeControl({ OriginTime: nowSeconds() - 305 });
		control.relativeFormat = null;
		control.render();
		expect(el.innerHTML).toBe('5 minutes');
	});

	it('falls back to the "other" plural pattern when PluralRules is unavailable', () => {
		const { control } = makeControl();
		control.pluralRules = null;
		expect(control.localizeUnit('minute', 1)).toBe('1 minutes');
	});
});

describe('TRelativeTime redraw loop', () => {
	it('stops scheduling once the element leaves the document', () => {
		const { control, el } = makeControl({ OriginTime: nowSeconds() - 305 });
		clearTimeout(control.loopTimeout);
		control.loopTimeout = null;
		el.remove();
		control.drawLoop();
		expect(control.loopTimeout).toBe(null);
	});
});

describe('TRelativeTime click toggle', () => {
	it('toggles to the absolute date and back', () => {
		const { control, el } = makeControl({ OriginTime: nowSeconds() - 305 });
		expect(el.innerHTML).toBe('5 minutes ago');
		control.toggleDisplay();
		expect(el.innerHTML).toBe('June 15, 2024');
		control.toggleDisplay();
		expect(el.innerHTML).toBe('5 minutes ago');
	});
});
