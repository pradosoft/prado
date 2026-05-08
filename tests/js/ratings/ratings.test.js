/**
 * Tests for TRatingList and TActiveRatingList (ratings.js).
 * Source: framework/Web/Javascripts/source/prado/ratings/ratings.js
 *
 * DOM structure expected by TRatingList.onInit:
 *
 *   <div id="{ID}">          — the rating widget container
 *     <table><tbody><tr>
 *       <td><input type="radio" id="{ID}_c0" value="Star 1" /></td>
 *       <td><input type="radio" id="{ID}_c1" value="Star 2" /></td>
 *       ...
 *     </tr></tbody></table>
 *   </div>
 *   <span id="{CaptionID}">initial caption</span>
 *
 * options shape (mirrors PHP TRatingList::getClientSideOptions):
 *   ID, CaptionID, ItemCount, Style, SelectedIndex, Rating,
 *   ReadOnly, AutoPostBack, HalfRating {"0": min, "1": max}, ListName
 *
 * ESM note: only tests/js/adapters/ratings.js changes on ESM conversion.
 */

import { TRatingList, TActiveRatingList } from '../adapters/ratings.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Build the DOM structure TRatingList.onInit expects and return base options.
 *
 * @param {string} id       - Base widget ID (e.g. "rating1").
 * @param {number} count    - Number of star radio buttons.
 * @param {object} overrides - Extra / override option fields.
 * @returns {{ options, cleanup }}
 */
function buildRatingDOM(id, count = 5, overrides = {}) {
	const captionId = id + '_cap';

	// Caption element
	const caption = document.createElement('span');
	caption.id = captionId;
	caption.innerHTML = 'Rate this';
	document.body.appendChild(caption);

	// Widget container
	const container = document.createElement('div');
	container.id = id;
	document.body.appendChild(container);

	// Table > tbody > tr with one <td> per radio.
	// The source does: var td = radio.parentNode.parentNode
	// then checks td.tagName == 'td', so the radio must be nested TWO levels
	// inside the <td> — matching the PHP render of <td><label><input/></label></td>.
	const table = document.createElement('table');
	const tbody = document.createElement('tbody');
	const tr = document.createElement('tr');

	for (let i = 0; i < count; i++) {
		const td = document.createElement('td');
		const label = document.createElement('label');
		const radio = document.createElement('input');
		radio.type = 'radio';
		radio.id = id + '_c' + i;
		radio.name = id;
		radio.value = 'Star ' + (i + 1);
		label.appendChild(radio);
		td.appendChild(label);
		tr.appendChild(td);
	}

	tbody.appendChild(tr);
	table.appendChild(tbody);
	container.appendChild(table);

	const options = Object.assign({
		ID: id,
		CaptionID: captionId,
		ItemCount: count,
		Style: 'rating_style',
		SelectedIndex: -1,
		Rating: -1,
		ReadOnly: false,
		AutoPostBack: false,
		HalfRating: { '0': 0.3, '1': 0.7 },
		ListName: id + '_list',
	}, overrides);

	const cleanup = () => {
		document.body.innerHTML = '';
		// Remove from Prado.Registry so a re-created control doesn't trigger replace()
		if (global.Prado && global.Prado.Registry) {
			delete global.Prado.Registry[id];
		}
	};

	return { options, cleanup };
}

// ─── TRatingList — class existence ───────────────────────────────────────────

describe('TRatingList class', () => {
	it('is defined', () => {
		expect(TRatingList).toBeDefined();
	});

	it('is a constructor function', () => {
		expect(typeof TRatingList).toBe('function');
	});
});

// ─── TRatingList — construction / onInit ─────────────────────────────────────

describe('TRatingList construction', () => {
	let rl, cleanup;

	beforeEach(() => {
		const built = buildRatingDOM('rl1', 5);
		cleanup = built.cleanup;
		rl = new TRatingList(built.options);
	});

	afterEach(() => cleanup());

	it('registers itself in Prado.Registry under the control ID', () => {
		expect(global.Prado.Registry['rl1']).toBe(rl);
	});

	it('collects exactly ItemCount radio elements', () => {
		expect(rl.radios).toHaveLength(5);
	});

	it('stores radio DOM elements (input type=radio)', () => {
		rl.radios.forEach(r => {
			expect(r.tagName.toLowerCase()).toBe('input');
			expect(r.type).toBe('radio');
		});
	});

	it('initialises selectedIndex from options', () => {
		expect(rl.selectedIndex).toBe(-1);
	});

	it('initialises rating from options', () => {
		expect(rl.rating).toBe(-1);
	});

	it('adds the Style CSS class to the container', () => {
		const container = document.getElementById('rl1');
		expect(container.classList.contains('rating_style')).toBe(true);
	});

	it('adds the "rating" class to each td', () => {
		const tds = document.querySelectorAll('#rl1 td');
		tds.forEach(td => expect(td.classList.contains('rating')).toBe(true));
	});
});

// ─── TRatingList — initial rating from SelectedIndex when Rating <= 0 ────────

describe('TRatingList rating fallback from SelectedIndex', () => {
	let rl, cleanup;

	beforeEach(() => {
		const built = buildRatingDOM('rl2', 5, { SelectedIndex: 2, Rating: -1 });
		cleanup = built.cleanup;
		rl = new TRatingList(built.options);
	});

	afterEach(() => cleanup());

	it('sets rating to SelectedIndex + 1 when Rating <= 0 and SelectedIndex >= 0', () => {
		expect(rl.rating).toBe(3);
	});
});

// ─── TRatingList — setRating ──────────────────────────────────────────────────

describe('TRatingList.setRating', () => {
	let rl, cleanup;

	beforeEach(() => {
		const built = buildRatingDOM('rl3', 5);
		cleanup = built.cleanup;
		rl = new TRatingList(built.options);
	});

	afterEach(() => cleanup());

	it('updates this.rating', () => {
		rl.setRating(3);
		expect(rl.rating).toBe(3);
	});

	it('checks exactly one radio (the index radio)', () => {
		rl.setRating(2); // base = floor(2-1) = 1; remainder = 0; index = 1
		const checkedCount = rl.radios.filter(r => r.checked).length;
		expect(checkedCount).toBe(1);
		expect(rl.radios[1].checked).toBe(true);
	});

	it('sets rating to 1 and checks radios[0]', () => {
		rl.setRating(1);
		expect(rl.radios[0].checked).toBe(true);
	});

	it('sets rating to 5 and checks radios[4]', () => {
		rl.setRating(5);
		expect(rl.radios[4].checked).toBe(true);
	});

	it('updates the caption to the radio value', () => {
		// setRating(3): base = floor(3-1) = 2, remainder = 0, halfMax = 0.7
		// index = remainder > halfMax ? base+1 : base = 2
		rl.setRating(3);
		const caption = document.getElementById('rl3_cap');
		expect(caption.innerHTML).toBe(rl.radios[2].value);
	});

	it('calls showRating with the new value', () => {
		const spy = vi.spyOn(rl, 'showRating');
		rl.setRating(3);
		expect(spy).toHaveBeenCalledWith(3);
	});
});

// ─── TRatingList — showRating ────────────────────────────────────────────────

describe('TRatingList.showRating', () => {
	let rl, cleanup;

	beforeEach(() => {
		const built = buildRatingDOM('rl4', 5);
		cleanup = built.cleanup;
		rl = new TRatingList(built.options);
	});

	afterEach(() => cleanup());

	it('adds "rating_selected" class to tds at or below the index', () => {
		rl.showRating(3); // base=2, tds 0,1,2 selected
		const tds = Array.from(document.querySelectorAll('#rl4 td'));
		expect(tds[0].classList.contains('rating_selected')).toBe(true);
		expect(tds[1].classList.contains('rating_selected')).toBe(true);
		expect(tds[2].classList.contains('rating_selected')).toBe(true);
	});

	it('removes "rating_selected" class from tds above the index', () => {
		rl.showRating(3);
		const tds = Array.from(document.querySelectorAll('#rl4 td'));
		expect(tds[3].classList.contains('rating_selected')).toBe(false);
		expect(tds[4].classList.contains('rating_selected')).toBe(false);
	});

	it('removes "rating_hover" class from all tds', () => {
		const tds = Array.from(document.querySelectorAll('#rl4 td'));
		tds.forEach(td => td.classList.add('rating_hover'));
		rl.showRating(3);
		tds.forEach(td => expect(td.classList.contains('rating_hover')).toBe(false));
	});

	it('adds "rating_half" to the td after the index when remainder is in half range', () => {
		// rating 2.5: base=1, remainder=0.5, halfMin=0.3, halfMax=0.7 → hasHalf=true, index+1=2
		rl.showRating(2.5);
		const tds = Array.from(document.querySelectorAll('#rl4 td'));
		expect(tds[2].classList.contains('rating_half')).toBe(true);
	});

	it('does not add "rating_half" when remainder is outside half range', () => {
		// rating 2.0: remainder=0, not in [0.3, 0.7] → no half
		rl.showRating(2.0);
		const tds = Array.from(document.querySelectorAll('#rl4 td'));
		tds.forEach(td => expect(td.classList.contains('rating_half')).toBe(false));
	});
});

// ─── TRatingList — hover ──────────────────────────────────────────────────────

describe('TRatingList.hover', () => {
	let rl, cleanup;

	beforeEach(() => {
		const built = buildRatingDOM('rl5', 5);
		cleanup = built.cleanup;
		rl = new TRatingList(built.options);
	});

	afterEach(() => cleanup());

	it('adds "rating_hover" to tds at or below the hovered index', () => {
		rl.hover(2, {});
		const tds = Array.from(document.querySelectorAll('#rl5 td'));
		expect(tds[0].classList.contains('rating_hover')).toBe(true);
		expect(tds[1].classList.contains('rating_hover')).toBe(true);
		expect(tds[2].classList.contains('rating_hover')).toBe(true);
	});

	it('removes "rating_hover" from tds above the hovered index', () => {
		rl.hover(2, {});
		const tds = Array.from(document.querySelectorAll('#rl5 td'));
		expect(tds[3].classList.contains('rating_hover')).toBe(false);
		expect(tds[4].classList.contains('rating_hover')).toBe(false);
	});

	it('removes "rating_selected" from all tds during hover', () => {
		const tds = Array.from(document.querySelectorAll('#rl5 td'));
		tds.forEach(td => td.classList.add('rating_selected'));
		rl.hover(2, {});
		tds.forEach(td => expect(td.classList.contains('rating_selected')).toBe(false));
	});

	it('removes "rating_half" from all tds during hover', () => {
		const tds = Array.from(document.querySelectorAll('#rl5 td'));
		tds.forEach(td => td.classList.add('rating_half'));
		rl.hover(2, {});
		tds.forEach(td => expect(td.classList.contains('rating_half')).toBe(false));
	});

	it('shows the hovered index caption', () => {
		rl.hover(1, {});
		const caption = document.getElementById('rl5_cap');
		expect(caption.innerHTML).toBe(rl.radios[1].value);
	});

	it('does nothing when readOnly is true', () => {
		rl.readOnly = true;
		rl.hover(2, {});
		const tds = Array.from(document.querySelectorAll('#rl5 td'));
		expect(tds[0].classList.contains('rating_hover')).toBe(false);
	});
});

// ─── TRatingList — recover ────────────────────────────────────────────────────

describe('TRatingList.recover', () => {
	let rl, cleanup;

	beforeEach(() => {
		const built = buildRatingDOM('rl6', 5, { Rating: 3 });
		cleanup = built.cleanup;
		rl = new TRatingList(built.options);
	});

	afterEach(() => cleanup());

	it('restores the display to the current rating', () => {
		const spy = vi.spyOn(rl, 'showRating');
		rl.hover(4, {});
		rl.recover(4, {});
		expect(spy).toHaveBeenLastCalledWith(rl.rating);
	});

	it('restores the caption to the stored options.caption', () => {
		rl.hover(4, {});
		rl.recover(4, {});
		const caption = document.getElementById('rl6_cap');
		expect(caption.innerHTML).toBe(rl.options.caption);
	});

	it('does nothing when readOnly is true', () => {
		rl.readOnly = true;
		const spy = vi.spyOn(rl, 'showRating');
		rl.recover(0, {});
		expect(spy).not.toHaveBeenCalled();
	});
});

// ─── TRatingList — click ──────────────────────────────────────────────────────

describe('TRatingList.click', () => {
	let rl, cleanup;

	beforeEach(() => {
		const built = buildRatingDOM('rl7', 5);
		cleanup = built.cleanup;
		rl = new TRatingList(built.options);
	});

	afterEach(() => cleanup());

	it('updates selectedIndex to the clicked index', () => {
		rl.click(2, {});
		expect(rl.selectedIndex).toBe(2);
	});

	it('calls setRating with index + 1', () => {
		const spy = vi.spyOn(rl, 'setRating');
		rl.click(3, {});
		expect(spy).toHaveBeenCalledWith(4);
	});

	it('does nothing when readOnly is true', () => {
		rl.readOnly = true;
		rl.click(2, {});
		expect(rl.selectedIndex).toBe(-1); // unchanged
	});

	it('calls dispatchRequest when AutoPostBack is true', () => {
		rl.options.AutoPostBack = true;
		const spy = vi.spyOn(rl, 'dispatchRequest').mockImplementation(() => {});
		rl.click(1, { preventDefault: vi.fn() });
		expect(spy).toHaveBeenCalled();
	});

	it('does not call dispatchRequest when AutoPostBack is false', () => {
		rl.options.AutoPostBack = false;
		const spy = vi.spyOn(rl, 'dispatchRequest').mockImplementation(() => {});
		rl.click(1, {});
		expect(spy).not.toHaveBeenCalled();
	});
});

// ─── TRatingList — getIndexCaption ───────────────────────────────────────────

describe('TRatingList.getIndexCaption', () => {
	let rl, cleanup;

	beforeEach(() => {
		const built = buildRatingDOM('rl8', 5);
		cleanup = built.cleanup;
		rl = new TRatingList(built.options);
	});

	afterEach(() => cleanup());

	it('returns the radio value for a valid index', () => {
		expect(rl.getIndexCaption(0)).toBe('Star 1');
		expect(rl.getIndexCaption(4)).toBe('Star 5');
	});

	it('returns options.caption when index is -1', () => {
		expect(rl.getIndexCaption(-1)).toBe(rl.options.caption);
	});
});

// ─── TRatingList — showCaption ────────────────────────────────────────────────

describe('TRatingList.showCaption', () => {
	let rl, cleanup;

	beforeEach(() => {
		const built = buildRatingDOM('rl9', 5);
		cleanup = built.cleanup;
		rl = new TRatingList(built.options);
	});

	afterEach(() => cleanup());

	it('sets the caption element innerHTML', () => {
		rl.showCaption('New caption');
		expect(document.getElementById('rl9_cap').innerHTML).toBe('New caption');
	});

	it('sets the container title attribute', () => {
		rl.showCaption('Title value');
		expect(document.getElementById('rl9').getAttribute('title')).toBe('Title value');
	});
});

// ─── TRatingList — setCaption ─────────────────────────────────────────────────

describe('TRatingList.setCaption', () => {
	let rl, cleanup;

	beforeEach(() => {
		const built = buildRatingDOM('rl10', 5);
		cleanup = built.cleanup;
		rl = new TRatingList(built.options);
	});

	afterEach(() => cleanup());

	it('updates options.caption', () => {
		rl.setCaption('Saved caption');
		expect(rl.options.caption).toBe('Saved caption');
	});

	it('also updates the DOM via showCaption', () => {
		rl.setCaption('DOM caption');
		expect(document.getElementById('rl10_cap').innerHTML).toBe('DOM caption');
	});
});

// ─── TRatingList — setReadOnly ────────────────────────────────────────────────

describe('TRatingList.setReadOnly', () => {
	describe('enabling read-only mode', () => {
		let rl, cleanup;

		beforeEach(() => {
			const built = buildRatingDOM('rl11', 3);
			cleanup = built.cleanup;
			rl = new TRatingList(built.options);
			rl.setReadOnly(true);
		});

		afterEach(() => cleanup());

		it('sets this.readOnly to true', () => {
			expect(rl.readOnly).toBe(true);
		});

		it('adds "rating_disabled" class to every td', () => {
			const tds = Array.from(document.querySelectorAll('#rl11 td'));
			tds.forEach(td => expect(td.classList.contains('rating_disabled')).toBe(true));
		});
	});

	describe('disabling read-only mode', () => {
		let rl, cleanup;

		beforeEach(() => {
			const built = buildRatingDOM('rl12', 3, { ReadOnly: true });
			cleanup = built.cleanup;
			rl = new TRatingList(built.options);
			rl.setReadOnly(false);
		});

		afterEach(() => cleanup());

		it('sets this.readOnly to false', () => {
			expect(rl.readOnly).toBe(false);
		});

		it('removes "rating_disabled" class from every td', () => {
			const tds = Array.from(document.querySelectorAll('#rl12 td'));
			tds.forEach(td => expect(td.classList.contains('rating_disabled')).toBe(false));
		});
	});

	describe('calls showRating after toggle', () => {
		let rl, cleanup;

		beforeEach(() => {
			const built = buildRatingDOM('rl13', 3);
			cleanup = built.cleanup;
			rl = new TRatingList(built.options);
		});

		afterEach(() => cleanup());

		it('calls showRating when enabling read-only', () => {
			const spy = vi.spyOn(rl, 'showRating');
			rl.setReadOnly(true);
			expect(spy).toHaveBeenCalled();
		});

		it('calls showRating when disabling read-only', () => {
			rl.readOnly = true;
			const spy = vi.spyOn(rl, 'showRating');
			rl.setReadOnly(false);
			expect(spy).toHaveBeenCalled();
		});
	});
});

// ─── TRatingList — dispatchRequest ───────────────────────────────────────────

describe('TRatingList.dispatchRequest', () => {
	let rl, cleanup;

	beforeEach(() => {
		const built = buildRatingDOM('rl14', 5, { AutoPostBack: true });
		cleanup = built.cleanup;
		rl = new TRatingList(built.options);
		rl.selectedIndex = 2;

		// Stub Prado.PostBack to avoid real form submission
		global.Prado.PostBack = vi.fn();
	});

	afterEach(() => {
		cleanup();
	});

	it('instantiates Prado.PostBack with ID set to the selected radio ID', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		rl.dispatchRequest(fakeEvent);
		expect(global.Prado.PostBack).toHaveBeenCalled();
		const calledOptions = global.Prado.PostBack.mock.calls[0][0];
		expect(calledOptions.ID).toBe('rl14_c2');
	});

	it('sets EventTarget to ListName$cIndex', () => {
		rl.dispatchRequest({ preventDefault: vi.fn() });
		const calledOptions = global.Prado.PostBack.mock.calls[0][0];
		expect(calledOptions.EventTarget).toBe('rl14_list$c2');
	});
});

// ─── TRatingList — edge cases ─────────────────────────────────────────────────

describe('TRatingList edge cases', () => {
	it('handles ItemCount of 1 correctly', () => {
		const { options, cleanup } = buildRatingDOM('rl15', 1);
		let rl;
		expect(() => { rl = new TRatingList(options); }).not.toThrow();
		expect(rl.radios).toHaveLength(1);
		cleanup();
	});

	it('handles ItemCount of 0 (no radios)', () => {
		const { options, cleanup } = buildRatingDOM('rl16', 0);
		let rl;
		expect(() => { rl = new TRatingList(options); }).not.toThrow();
		expect(rl.radios).toHaveLength(0);
		cleanup();
	});

	it('setRating with value 0 does not crash', () => {
		const { options, cleanup } = buildRatingDOM('rl17', 5);
		const rl = new TRatingList(options);
		expect(() => rl.setRating(0)).not.toThrow();
		cleanup();
	});

	it('hover with index -1 does not crash', () => {
		const { options, cleanup } = buildRatingDOM('rl18', 5);
		const rl = new TRatingList(options);
		expect(() => rl.hover(-1, {})).not.toThrow();
		cleanup();
	});

	it('preserves caption from CaptionID element', () => {
		const { options, cleanup } = buildRatingDOM('rl19', 3);
		document.getElementById('rl19_cap').innerHTML = 'Custom caption';
		const rl = new TRatingList(options);
		expect(rl.options.caption).toBe('Custom caption');
		cleanup();
	});

	it('caption defaults to empty string when CaptionID element is absent', () => {
		const { options, cleanup } = buildRatingDOM('rl20', 3, { CaptionID: 'nonexistent_cap' });
		const rl = new TRatingList(options);
		expect(rl.options.caption).toBe('');
		cleanup();
	});
});

// ─── TActiveRatingList — class existence and inheritance ─────────────────────

describe('TActiveRatingList', () => {
	it('is defined', () => {
		expect(TActiveRatingList).toBeDefined();
	});

	it('is a constructor function', () => {
		expect(typeof TActiveRatingList).toBe('function');
	});

	it('shares the same prototype chain as TRatingList', () => {
		const { options, cleanup } = buildRatingDOM('arl1', 3);
		const arl = new TActiveRatingList(options);
		expect(arl instanceof TActiveRatingList).toBe(true);
		// TActiveRatingList inherits from TRatingList via jQuery.klass
		expect(typeof arl.hover).toBe('function');
		expect(typeof arl.setRating).toBe('function');
		expect(typeof arl.setReadOnly).toBe('function');
		cleanup();
	});

	describe('dispatchRequest override', () => {
		let arl, cleanup;

		beforeEach(() => {
			const built = buildRatingDOM('arl2', 3, { AutoPostBack: true });
			cleanup = built.cleanup;
			arl = new TActiveRatingList(built.options);
			arl.selectedIndex = 1;

			// Stub Prado.CallbackRequest — used with `new`, so assign a real function.
			// When called with `new`, the constructor's `this` is the instance.
			const mockDispatch = vi.fn().mockReturnValue(true);
			function FakeCallbackRequest() {
				this.dispatch = mockDispatch;
			}
			global.Prado.CallbackRequest = FakeCallbackRequest;
		});

		afterEach(() => cleanup());

		it('instantiates Prado.CallbackRequest (not Prado.PostBack)', () => {
			// Verify PostBack is not called (only CallbackRequest should be used).
			const PostBackSpy = vi.fn();
			global.Prado.PostBack = PostBackSpy;
			// Replace with a tracking constructor
			let dispatchCalled = false;
			function TrackingCR() { this.dispatch = () => { dispatchCalled = true; return true; }; }
			global.Prado.CallbackRequest = TrackingCR;
			arl.dispatchRequest({ preventDefault: vi.fn() });
			expect(PostBackSpy).not.toHaveBeenCalled();
			expect(dispatchCalled).toBe(true);
		});

		it('passes EventTarget as ListName$cIndex', () => {
			// The source passes eventTarget as the first arg to CallbackRequest.
			// Capture it by intercepting via a wrapper constructor.
			let capturedTarget;
			const origCR = global.Prado.CallbackRequest;
			function CapturingCR(target) {
				capturedTarget = target;
				this.dispatch = vi.fn().mockReturnValue(true);
			}
			global.Prado.CallbackRequest = CapturingCR;
			arl.dispatchRequest({ preventDefault: vi.fn() });
			global.Prado.CallbackRequest = origCR;
			expect(capturedTarget).toBe('arl2_list$c1');
		});

		it('calls preventDefault when dispatch returns false', () => {
			function FalseDispatchCR() { this.dispatch = vi.fn().mockReturnValue(false); }
			global.Prado.CallbackRequest = FalseDispatchCR;
			const ev = { preventDefault: vi.fn() };
			arl.dispatchRequest(ev);
			expect(ev.preventDefault).toHaveBeenCalled();
		});

		it('does not call preventDefault when dispatch returns true', () => {
			// mockDispatch returns true (set in beforeEach).
			const ev = { preventDefault: vi.fn() };
			arl.dispatchRequest(ev);
			expect(ev.preventDefault).not.toHaveBeenCalled();
		});
	});
});
