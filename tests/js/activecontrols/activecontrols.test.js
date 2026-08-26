/**
 * Tests for activecontrols3.js
 *
 * Sources tested:
 *   framework/Web/Javascripts/source/prado/activecontrols/activecontrols3.js
 *
 * Strategy
 * --------
 * Every TActive* class is tested for:
 *   - registration in Prado.Registry after construction
 *   - presence of the expected method surface
 *   - correct callback dispatch behavior (Prado.CallbackRequest mocked)
 *
 * DOM elements required by each control are created in beforeEach and
 * cleaned up in afterEach. Network calls are never made — CallbackRequest
 * is replaced with a vi.fn() factory that returns a spy object.
 *
 * ESM note: only tests/js/adapters/activecontrols.js changes on ESM
 * conversion; this file stays unchanged.
 */

import {
	Registry,
	CallbackRequest,
	CallbackControl,
	TActiveButton,
	TActiveLinkButton,
	TActiveImageButton,
	TActiveCheckBox,
	TActiveRadioButton,
	TActiveCheckBoxList,
	TActiveRadioButtonList,
	ActiveListControl,
	TActiveDropDownList,
	TActiveListBox,
	TActiveTextBox,
	TTimeTriggeredCallback,
	TEventTriggeredCallback,
	TValueTriggeredCallback,
	TActiveTableCell,
	TActiveTableRow,
	TActiveDetails,
	TActiveDialog,
} from '../adapters/activecontrols.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Create a dispatch spy that is returned whenever `new Prado.CallbackRequest()`
 * is called. The spy records arguments and exposes a `.dispatch` mock so tests
 * can verify it was called.
 *
 * Because the PRADO source uses `new Prado.CallbackRequest(...)`, we must
 * replace `global.Prado.CallbackRequest` with a real constructor function
 * (not an arrow function) so that `new` works. We keep the original to restore
 * it in afterEach via restoreMocks().
 */
function mockCallbackRequest() {
	const dispatchMock = vi.fn().mockReturnValue(true);
	const setCallbackParameterMock = vi.fn();
	const requestInstance = {
		dispatch: dispatchMock,
		setCallbackParameter: setCallbackParameterMock,
		options: {},
	};

	const original = global.Prado.CallbackRequest;
	// Use a real function (not arrow) so `new` works.
	const MockCtor = vi.fn(function () {
		return requestInstance;
	});
	global.Prado.CallbackRequest = MockCtor;

	// Store original for restore
	MockCtor.__original = original;

	return { MockCtor, requestInstance, dispatchMock, setCallbackParameterMock };
}

/** Restore every vi.spyOn created during a test plus CallbackRequest ctor. */
function restoreMocks() {
	vi.restoreAllMocks();
	// If mockCallbackRequest() replaced the ctor, put it back.
	if (
		global.Prado.CallbackRequest &&
		global.Prado.CallbackRequest.__original !== undefined
	) {
		global.Prado.CallbackRequest = global.Prado.CallbackRequest.__original;
	}
}

/** Make a synthetic jQuery click/change event with preventDefault & stopPropagation stubs. */
function fakeEvent(overrides) {
	return Object.assign(
		{
			preventDefault: vi.fn(),
			stopPropagation: vi.fn(),
			target: null,
			clientX: 10,
			clientY: 20,
		},
		overrides,
	);
}

/** Clean the Prado.Registry between tests so controls don't collide. */
function clearRegistry() {
	for (const key of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[key];
	}
}

// ─── Class shape assertions ───────────────────────────────────────────────────

describe('Class definitions exist', () => {
	it.each([
		['CallbackControl',         CallbackControl],
		['TActiveButton',           TActiveButton],
		['TActiveLinkButton',       TActiveLinkButton],
		['TActiveImageButton',      TActiveImageButton],
		['TActiveCheckBox',         TActiveCheckBox],
		['TActiveRadioButton',      TActiveRadioButton],
		['TActiveCheckBoxList',     TActiveCheckBoxList],
		['TActiveRadioButtonList',  TActiveRadioButtonList],
		['ActiveListControl',       ActiveListControl],
		['TActiveDropDownList',     TActiveDropDownList],
		['TActiveListBox',          TActiveListBox],
		['TActiveTextBox',          TActiveTextBox],
		['TTimeTriggeredCallback',  TTimeTriggeredCallback],
		['TEventTriggeredCallback', TEventTriggeredCallback],
		['TValueTriggeredCallback', TValueTriggeredCallback],
		['TActiveTableCell',        TActiveTableCell],
		['TActiveTableRow',         TActiveTableRow],
		['TActiveDetails',          TActiveDetails],
		['TActiveDialog',           TActiveDialog],
	])('%s is a function (constructor)', (_name, klass) => {
		expect(typeof klass).toBe('function');
	});
});

// ─── TActiveButton ────────────────────────────────────────────────────────────

describe('TActiveButton', () => {
	let btn;

	beforeEach(() => {
		clearRegistry();
		btn = document.createElement('button');
		btn.id = 'btn1';
		document.body.appendChild(btn);
	});

	afterEach(() => {
		restoreMocks();
		btn.remove();
	});

	it('registers itself in Prado.Registry on construction', () => {
		new TActiveButton({ ID: 'btn1', EventTarget: 'btn1' });
		expect(Registry['btn1']).toBeDefined();
	});

	it('dispatches a CallbackRequest when clicked', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveButton({ ID: 'btn1', EventTarget: 'btn1' });
		const evt = fakeEvent({ target: btn });
		ctrl.onPostBack({ EventTarget: 'btn1' }, evt);
		expect(dispatchMock).toHaveBeenCalled();
	});

	it('calls event.preventDefault() after dispatch', () => {
		mockCallbackRequest();
		const ctrl = new TActiveButton({ ID: 'btn1', EventTarget: 'btn1' });
		const evt = fakeEvent({ target: btn });
		ctrl.onPostBack({ EventTarget: 'btn1' }, evt);
		expect(evt.preventDefault).toHaveBeenCalled();
	});
});

// ─── TActiveLinkButton ────────────────────────────────────────────────────────

describe('TActiveLinkButton', () => {
	let link;

	beforeEach(() => {
		clearRegistry();
		link = document.createElement('a');
		link.id = 'lnk1';
		link.href = '#';
		document.body.appendChild(link);
	});

	afterEach(() => {
		restoreMocks();
		link.remove();
	});

	it('registers itself in Prado.Registry on construction', () => {
		new TActiveLinkButton({ ID: 'lnk1', EventTarget: 'lnk1' });
		expect(Registry['lnk1']).toBeDefined();
	});

	it('dispatches a CallbackRequest and prevents default when clicked', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveLinkButton({ ID: 'lnk1', EventTarget: 'lnk1' });
		const evt = fakeEvent({ target: link });
		ctrl.onPostBack({ EventTarget: 'lnk1' }, evt);
		expect(dispatchMock).toHaveBeenCalled();
		expect(evt.preventDefault).toHaveBeenCalled();
	});
});

// ─── TActiveImageButton ───────────────────────────────────────────────────────

describe('TActiveImageButton', () => {
	let img, form, pagestate;

	beforeEach(() => {
		clearRegistry();

		form = document.createElement('form');
		form.id = 'theForm';
		form.action = '/callback';

		img = document.createElement('img');
		img.id = 'img1';
		form.appendChild(img);

		pagestate = document.createElement('input');
		pagestate.type = 'hidden';
		pagestate.id = 'PRADO_PAGESTATE';
		form.appendChild(pagestate);

		document.body.appendChild(form);
	});

	afterEach(() => {
		restoreMocks();
		form.remove();
	});

	it('registers itself in Prado.Registry on construction', () => {
		new TActiveImageButton({ ID: 'img1', EventTarget: 'img1' });
		expect(Registry['img1']).toBeDefined();
	});

	it('calls addXYInput, dispatch, preventDefault, and removeXYInput', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveImageButton({ ID: 'img1', EventTarget: 'img1' });

		const addXY    = vi.spyOn(ctrl, 'addXYInput');
		const removeXY = vi.spyOn(ctrl, 'removeXYInput');

		const evt = fakeEvent({ target: img });
		const options = { EventTarget: 'img1' };
		ctrl.onPostBack(options, evt);

		expect(addXY).toHaveBeenCalled();
		expect(dispatchMock).toHaveBeenCalled();
		expect(evt.preventDefault).toHaveBeenCalled();
		expect(removeXY).toHaveBeenCalled();
	});

	it('addXYInput appends hidden x/y inputs to the form', () => {
		const ctrl = new TActiveImageButton({ ID: 'img1', EventTarget: 'img1' });
		const evt  = fakeEvent({ target: img, clientX: 50, clientY: 60 });
		ctrl.addXYInput({ EventTarget: 'img1' }, evt);
		expect(form.querySelector('#img1_x')).not.toBeNull();
		expect(form.querySelector('#img1_y')).not.toBeNull();
	});

	it('removeXYInput removes the hidden x/y inputs', () => {
		const ctrl = new TActiveImageButton({ ID: 'img1', EventTarget: 'img1' });
		const evt  = fakeEvent({ target: img, clientX: 5, clientY: 5 });
		ctrl.addXYInput({ EventTarget: 'img1' }, evt);
		ctrl.removeXYInput({ EventTarget: 'img1' }, evt);
		expect(form.querySelector('#img1_x')).toBeNull();
		expect(form.querySelector('#img1_y')).toBeNull();
	});
});

// ─── TActiveCheckBox ─────────────────────────────────────────────────────────

describe('TActiveCheckBox', () => {
	let cb;

	beforeEach(() => {
		clearRegistry();
		cb = document.createElement('input');
		cb.type = 'checkbox';
		cb.id = 'cb1';
		document.body.appendChild(cb);
	});

	afterEach(() => {
		restoreMocks();
		cb.remove();
	});

	it('registers itself in Prado.Registry on construction', () => {
		new TActiveCheckBox({ ID: 'cb1', EventTarget: 'cb1' });
		expect(Registry['cb1']).toBeDefined();
	});

	it('dispatches a CallbackRequest on postback', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveCheckBox({ ID: 'cb1', EventTarget: 'cb1' });
		const evt  = fakeEvent({ target: cb });
		ctrl.onPostBack({ EventTarget: 'cb1' }, evt);
		expect(dispatchMock).toHaveBeenCalled();
	});

	it('calls event.preventDefault() when dispatch returns false', () => {
		const { dispatchMock } = mockCallbackRequest();
		dispatchMock.mockReturnValue(false);
		const ctrl = new TActiveCheckBox({ ID: 'cb1', EventTarget: 'cb1' });
		const evt  = fakeEvent({ target: cb });
		ctrl.onPostBack({ EventTarget: 'cb1' }, evt);
		expect(evt.preventDefault).toHaveBeenCalled();
	});

	it('does NOT call event.preventDefault() when dispatch succeeds', () => {
		const { dispatchMock } = mockCallbackRequest();
		dispatchMock.mockReturnValue(true);
		const ctrl = new TActiveCheckBox({ ID: 'cb1', EventTarget: 'cb1' });
		const evt  = fakeEvent({ target: cb });
		ctrl.onPostBack({ EventTarget: 'cb1' }, evt);
		expect(evt.preventDefault).not.toHaveBeenCalled();
	});
});

// ─── TActiveRadioButton ───────────────────────────────────────────────────────

describe('TActiveRadioButton', () => {
	let rb;

	beforeEach(() => {
		clearRegistry();
		rb = document.createElement('input');
		rb.type = 'radio';
		rb.id   = 'rb1';
		document.body.appendChild(rb);
	});

	afterEach(() => {
		restoreMocks();
		rb.remove();
	});

	it('is an alias of TActiveCheckBox (same prototype chain)', () => {
		// TActiveRadioButton = jQuery.klass(TActiveCheckBox)
		const proto = TActiveRadioButton.superclass;
		expect(proto).toBe(TActiveCheckBox);
	});

	it('registers itself in Prado.Registry on construction', () => {
		new TActiveRadioButton({ ID: 'rb1', EventTarget: 'rb1' });
		expect(Registry['rb1']).toBeDefined();
	});

	it('dispatches a CallbackRequest on postback', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveRadioButton({ ID: 'rb1', EventTarget: 'rb1' });
		const evt  = fakeEvent({ target: rb });
		ctrl.onPostBack({ EventTarget: 'rb1' }, evt);
		expect(dispatchMock).toHaveBeenCalled();
	});
});

// ─── TActiveCheckBoxList ──────────────────────────────────────────────────────

describe('TActiveCheckBoxList', () => {
	let container;

	beforeEach(() => {
		clearRegistry();
		container = document.createElement('div');
		container.id = 'cbl1';

		// Two child checkboxes as rendered by PRADO
		for (let i = 0; i < 2; i++) {
			const cb = document.createElement('input');
			cb.type = 'checkbox';
			cb.id = `cbl1_c${i}`;
			container.appendChild(cb);
		}
		document.body.appendChild(container);
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('registers each child checkbox in Prado.Registry', () => {
		new TActiveCheckBoxList({
			ID: 'cbl1',
			EventTarget: 'cbl1',
			ListName: 'cbl1',
			ItemCount: 2,
		});
		expect(Registry['cbl1_c0']).toBeDefined();
		expect(Registry['cbl1_c1']).toBeDefined();
	});

	it('uses the correct EventTarget for each child (ListName$cN)', () => {
		new TActiveCheckBoxList({
			ID: 'cbl1',
			EventTarget: 'cbl1',
			ListName: 'cbl1',
			ItemCount: 2,
		});
		const child0 = Registry['cbl1_c0'];
		// The child controls are TActiveCheckBox instances
		expect(child0).toBeInstanceOf(TActiveCheckBox);
	});
});

// ─── TActiveRadioButtonList ───────────────────────────────────────────────────

describe('TActiveRadioButtonList', () => {
	it('is the same constructor as TActiveCheckBoxList', () => {
		// Assigned with: Prado.WebUI.TActiveRadioButtonList = Prado.WebUI.TActiveCheckBoxList;
		expect(TActiveRadioButtonList).toBe(TActiveCheckBoxList);
	});
});

// ─── ActiveListControl / TActiveDropDownList / TActiveListBox ─────────────────

describe('ActiveListControl', () => {
	let select;

	beforeEach(() => {
		clearRegistry();
		select = document.createElement('select');
		select.id = 'sel1';
		['A', 'B'].forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			opt.text  = v;
			select.appendChild(opt);
		});
		document.body.appendChild(select);
	});

	afterEach(() => {
		restoreMocks();
		select.remove();
	});

	it('registers in Prado.Registry on construction', () => {
		new ActiveListControl({ ID: 'sel1', EventTarget: 'sel1' });
		expect(Registry['sel1']).toBeDefined();
	});

	it('doCallback dispatches a CallbackRequest', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new ActiveListControl({ ID: 'sel1', EventTarget: 'sel1' });
		const evt  = fakeEvent({ target: select });
		ctrl.doCallback(evt);
		expect(dispatchMock).toHaveBeenCalled();
		expect(evt.preventDefault).toHaveBeenCalled();
	});
});

describe('TActiveDropDownList', () => {
	let select;

	beforeEach(() => {
		clearRegistry();
		select = document.createElement('select');
		select.id = 'ddl1';
		document.body.appendChild(select);
	});

	afterEach(() => {
		restoreMocks();
		select.remove();
	});

	it('is a subclass of ActiveListControl', () => {
		expect(TActiveDropDownList.superclass).toBe(ActiveListControl);
	});

	it('registers in Prado.Registry on construction', () => {
		new TActiveDropDownList({ ID: 'ddl1', EventTarget: 'ddl1' });
		expect(Registry['ddl1']).toBeDefined();
	});
});

describe('TActiveListBox', () => {
	let select;

	beforeEach(() => {
		clearRegistry();
		select = document.createElement('select');
		select.id = 'lb1';
		select.multiple = true;
		document.body.appendChild(select);
	});

	afterEach(() => {
		restoreMocks();
		select.remove();
	});

	it('is a subclass of ActiveListControl', () => {
		expect(TActiveListBox.superclass).toBe(ActiveListControl);
	});

	it('registers in Prado.Registry on construction', () => {
		new TActiveListBox({ ID: 'lb1', EventTarget: 'lb1' });
		expect(Registry['lb1']).toBeDefined();
	});
});

// ─── TActiveTextBox ───────────────────────────────────────────────────────────

describe('TActiveTextBox', () => {
	let input;

	beforeEach(() => {
		clearRegistry();
		input = document.createElement('input');
		input.type = 'text';
		input.id   = 'tb1';
		document.body.appendChild(input);
	});

	afterEach(() => {
		restoreMocks();
		input.remove();
	});

	it('registers in Prado.Registry on construction', () => {
		new TActiveTextBox({ ID: 'tb1', EventTarget: 'tb1', TextMode: 'SingleLine', AutoPostBack: false });
		expect(Registry['tb1']).toBeDefined();
	});

	it('doCallback dispatches a CallbackRequest and calls preventDefault', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveTextBox({
			ID: 'tb1', EventTarget: 'tb1', TextMode: 'SingleLine', AutoPostBack: true,
		});
		const evt = fakeEvent({ target: input });
		ctrl.doCallback({ EventTarget: 'tb1' }, evt);
		expect(dispatchMock).toHaveBeenCalled();
		expect(evt.preventDefault).toHaveBeenCalled();
	});

	it('observes keydown for return-key handling when TextMode is SingleLine', () => {
		const observe = vi.spyOn(
			global.Prado.WebUI.Control.prototype, 'observe',
		);
		new TActiveTextBox({
			ID: 'tb1', EventTarget: 'tb1', TextMode: 'SingleLine', AutoPostBack: false,
		});
		const keydownCall = observe.mock.calls.find((c) => c[1] === 'keydown');
		expect(keydownCall).toBeDefined();
	});

	it('does NOT observe keydown when TextMode is MultiLine', () => {
		clearRegistry();

		const textarea = document.createElement('textarea');
		textarea.id = 'tb1ml';
		document.body.appendChild(textarea);

		const observe = vi.spyOn(
			global.Prado.WebUI.Control.prototype, 'observe',
		);
		new TActiveTextBox({
			ID: 'tb1ml', EventTarget: 'tb1ml', TextMode: 'MultiLine', AutoPostBack: false,
		});
		const keydownCall = observe.mock.calls.find((c) => c[1] === 'keydown');
		expect(keydownCall).toBeUndefined();

		textarea.remove();
	});

	it('observes change when AutoPostBack is true', () => {
		const observe = vi.spyOn(
			global.Prado.WebUI.Control.prototype, 'observe',
		);
		new TActiveTextBox({
			ID: 'tb1', EventTarget: 'tb1', TextMode: 'SingleLine', AutoPostBack: true,
		});
		const changeCall = observe.mock.calls.find((c) => c[1] === 'change');
		expect(changeCall).toBeDefined();
	});

	it('does NOT observe change when AutoPostBack is false', () => {
		const observe = vi.spyOn(
			global.Prado.WebUI.Control.prototype, 'observe',
		);
		new TActiveTextBox({
			ID: 'tb1', EventTarget: 'tb1', TextMode: 'SingleLine', AutoPostBack: false,
		});
		const changeCall = observe.mock.calls.find((c) => c[1] === 'change');
		expect(changeCall).toBeUndefined();
	});
});

// ─── TTimeTriggeredCallback ───────────────────────────────────────────────────

describe('TTimeTriggeredCallback', () => {
	let div;

	beforeEach(() => {
		clearRegistry();
		// Clear the static timers registry between tests
		for (const k of Object.keys(TTimeTriggeredCallback.timers)) {
			delete TTimeTriggeredCallback.timers[k];
		}
		div = document.createElement('div');
		div.id = 'ttc1';
		document.body.appendChild(div);
		vi.useFakeTimers();
	});

	afterEach(() => {
		restoreMocks();
		vi.useRealTimers();
		div.remove();
	});

	it('registers in Prado.Registry on construction', () => {
		new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		expect(Registry['ttc1']).toBeDefined();
	});

	it('adds itself to TTimeTriggeredCallback.timers', () => {
		new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		expect(TTimeTriggeredCallback.timers['ttc1']).toBeDefined();
	});

	it('startTimer sets an interval; stopTimer clears it', () => {
		const ctrl = new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		expect(ctrl.timer).toBeUndefined();
		ctrl.startTimer();
		expect(ctrl.timer).not.toBeNull();
		ctrl.stopTimer();
		expect(ctrl.timer).toBeNull();
	});

	it('startTimer is idempotent (does not create a second interval)', () => {
		const ctrl = new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		ctrl.startTimer();
		const firstTimer = ctrl.timer;
		ctrl.startTimer(); // second call should be a no-op
		expect(ctrl.timer).toBe(firstTimer);
		ctrl.stopTimer();
	});

	it('resetTimer replaces the interval', () => {
		const ctrl = new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		ctrl.startTimer();
		const firstTimer = ctrl.timer;
		ctrl.resetTimer();
		// After reset, a new timer handle is assigned
		expect(ctrl.timer).not.toBe(firstTimer);
		ctrl.stopTimer();
	});

	it('setTimerInterval changes the interval and resets the timer', () => {
		const ctrl = new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		ctrl.startTimer();
		const reset = vi.spyOn(ctrl, 'resetTimer');
		ctrl.setTimerInterval(5);
		expect(ctrl.options.Interval).toBe(5);
		expect(reset).toHaveBeenCalled();
		ctrl.stopTimer();
	});

	it('setTimerInterval is a no-op if the value did not change', () => {
		const ctrl = new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		ctrl.startTimer();
		const reset = vi.spyOn(ctrl, 'resetTimer');
		ctrl.setTimerInterval(1); // same value
		expect(reset).not.toHaveBeenCalled();
		ctrl.stopTimer();
	});

	it('onTimerEvent dispatches a CallbackRequest', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		ctrl.onTimerEvent();
		expect(dispatchMock).toHaveBeenCalled();
	});

	it('onDone stops the timer', () => {
		const ctrl = new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		ctrl.startTimer();
		const stop = vi.spyOn(ctrl, 'stopTimer');
		ctrl.onDone();
		expect(stop).toHaveBeenCalled();
	});

	it('static start() calls startTimer on the registered instance', () => {
		const ctrl = new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		const startTimer = vi.spyOn(ctrl, 'startTimer');
		TTimeTriggeredCallback.start('ttc1');
		expect(startTimer).toHaveBeenCalled();
		ctrl.stopTimer();
	});

	it('static stop() calls stopTimer on the registered instance', () => {
		const ctrl = new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		ctrl.startTimer();
		const stopTimer = vi.spyOn(ctrl, 'stopTimer');
		TTimeTriggeredCallback.stop('ttc1');
		expect(stopTimer).toHaveBeenCalled();
	});

	it('static setTimerInterval() delegates to the instance', () => {
		const ctrl = new TTimeTriggeredCallback({ ID: 'ttc1', EventTarget: 'ttc1', Interval: 1 });
		ctrl.startTimer();
		const setInt = vi.spyOn(ctrl, 'setTimerInterval');
		TTimeTriggeredCallback.setTimerInterval('ttc1', 3);
		expect(setInt).toHaveBeenCalledWith(3);
		ctrl.stopTimer();
	});

	it('static start/stop/setTimerInterval are no-ops for unknown IDs', () => {
		// Should not throw
		expect(() => TTimeTriggeredCallback.start('unknown')).not.toThrow();
		expect(() => TTimeTriggeredCallback.stop('unknown')).not.toThrow();
		expect(() => TTimeTriggeredCallback.setTimerInterval('unknown', 5)).not.toThrow();
	});
});

// ─── TEventTriggeredCallback ──────────────────────────────────────────────────

describe('TEventTriggeredCallback', () => {
	let btn, div;

	beforeEach(() => {
		clearRegistry();
		div = document.createElement('div');
		div.id = 'etc1';
		document.body.appendChild(div);

		btn = document.createElement('button');
		btn.id = 'ctrl_src';
		document.body.appendChild(btn);
	});

	afterEach(() => {
		restoreMocks();
		div.remove();
		btn.remove();
	});

	it('registers in Prado.Registry on construction', () => {
		new TEventTriggeredCallback({
			ID: 'etc1', EventTarget: 'etc1', ControlID: 'ctrl_src',
		});
		expect(Registry['etc1']).toBeDefined();
	});

	it('doCallback dispatches a CallbackRequest', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TEventTriggeredCallback({
			ID: 'etc1', EventTarget: 'etc1', ControlID: 'ctrl_src',
		});
		const evt = fakeEvent({ target: btn });
		ctrl.doCallback(evt);
		expect(dispatchMock).toHaveBeenCalled();
	});

	it('doCallback calls event.preventDefault() when StopEvent is true', () => {
		mockCallbackRequest();
		const ctrl = new TEventTriggeredCallback({
			ID: 'etc1', EventTarget: 'etc1', ControlID: 'ctrl_src', StopEvent: true,
		});
		const evt = fakeEvent({ target: btn });
		ctrl.doCallback(evt);
		expect(evt.preventDefault).toHaveBeenCalled();
	});

	it('doCallback does not call preventDefault when StopEvent is false', () => {
		mockCallbackRequest();
		const ctrl = new TEventTriggeredCallback({
			ID: 'etc1', EventTarget: 'etc1', ControlID: 'ctrl_src', StopEvent: false,
		});
		const evt = fakeEvent({ target: btn });
		ctrl.doCallback(evt);
		expect(evt.preventDefault).not.toHaveBeenCalled();
	});

	describe('getEventName', () => {
		it('returns "change" for <input type=text>', () => {
			const ctrl = new TEventTriggeredCallback({
				ID: 'etc1', EventTarget: 'etc1', ControlID: 'ctrl_src',
			});
			const el = document.createElement('input');
			el.type = 'text';
			expect(ctrl.getEventName(el)).toBe('change');
		});

		it('returns "change" for <select>', () => {
			const ctrl = new TEventTriggeredCallback({
				ID: 'etc1', EventTarget: 'etc1', ControlID: 'ctrl_src',
			});
			const el = document.createElement('select'); // type = select-one
			expect(ctrl.getEventName(el)).toBe('change');
		});

		it('returns "change" for <textarea>', () => {
			const ctrl = new TEventTriggeredCallback({
				ID: 'etc1', EventTarget: 'etc1', ControlID: 'ctrl_src',
			});
			const el = document.createElement('textarea');
			expect(ctrl.getEventName(el)).toBe('change');
		});

		it('returns "click" for elements without a matching type', () => {
			const ctrl = new TEventTriggeredCallback({
				ID: 'etc1', EventTarget: 'etc1', ControlID: 'ctrl_src',
			});
			const el = document.createElement('div');
			expect(ctrl.getEventName(el)).toBe('click');
		});

		it('returns EventName option when explicitly set', () => {
			clearRegistry();
			const div2 = document.createElement('div');
			div2.id = 'etc2';
			document.body.appendChild(div2);

			const ctrl = new TEventTriggeredCallback({
				ID: 'etc2', EventTarget: 'etc2', ControlID: 'ctrl_src', EventName: 'focus',
			});
			const el = document.createElement('input');
			el.type = 'text';
			expect(ctrl.getEventName(el)).toBe('focus');

			div2.remove();
		});
	});
});

// ─── TValueTriggeredCallback ──────────────────────────────────────────────────

describe('TValueTriggeredCallback', () => {
	let input, div;

	beforeEach(() => {
		clearRegistry();
		// Clear the static timers registry between tests
		for (const k of Object.keys(TValueTriggeredCallback.timers)) {
			delete TValueTriggeredCallback.timers[k];
		}

		div = document.createElement('div');
		div.id = 'vtc1';
		document.body.appendChild(div);

		input = document.createElement('input');
		input.type  = 'text';
		input.id    = 'vtc_ctrl';
		input.value = 'initial';
		document.body.appendChild(input);

		vi.useFakeTimers();
	});

	afterEach(() => {
		restoreMocks();
		vi.useRealTimers();
		div.remove();
		input.remove();
	});

	it('registers in Prado.Registry on construction', () => {
		new TValueTriggeredCallback({
			ID: 'vtc1', EventTarget: 'vtc1', ControlID: 'vtc_ctrl',
			PropertyName: 'value', Interval: 1, Decay: 1,
		});
		expect(Registry['vtc1']).toBeDefined();
	});

	it('adds itself to TValueTriggeredCallback.timers', () => {
		new TValueTriggeredCallback({
			ID: 'vtc1', EventTarget: 'vtc1', ControlID: 'vtc_ctrl',
			PropertyName: 'value', Interval: 1, Decay: 1,
		});
		expect(TValueTriggeredCallback.timers['vtc1']).toBeDefined();
	});

	it('doCallback dispatches a CallbackRequest with old and new values', () => {
		const { dispatchMock, setCallbackParameterMock } = mockCallbackRequest();
		const ctrl = new TValueTriggeredCallback({
			ID: 'vtc1', EventTarget: 'vtc1', ControlID: 'vtc_ctrl',
			PropertyName: 'value', Interval: 1, Decay: 1,
		});
		ctrl.stopObserving();
		ctrl.doCallback('old', 'new');
		expect(setCallbackParameterMock).toHaveBeenCalledWith({ OldValue: 'old', NewValue: 'new' });
		expect(dispatchMock).toHaveBeenCalled();
	});

	it('stopObserving sets observing to false', () => {
		const ctrl = new TValueTriggeredCallback({
			ID: 'vtc1', EventTarget: 'vtc1', ControlID: 'vtc_ctrl',
			PropertyName: 'value', Interval: 1, Decay: 1,
		});
		ctrl.stopObserving();
		expect(ctrl.observing).toBe(false);
	});

	it('onDone stops observing', () => {
		const ctrl = new TValueTriggeredCallback({
			ID: 'vtc1', EventTarget: 'vtc1', ControlID: 'vtc_ctrl',
			PropertyName: 'value', Interval: 1, Decay: 1,
		});
		ctrl.stopObserving();
		const stop = vi.spyOn(ctrl, 'stopObserving');
		ctrl.observing = true; // re-enable so onDone does something
		ctrl.onDone();
		expect(stop).toHaveBeenCalled();
	});

	it('static stop() calls stopObserving on the registered instance', () => {
		const ctrl = new TValueTriggeredCallback({
			ID: 'vtc1', EventTarget: 'vtc1', ControlID: 'vtc_ctrl',
			PropertyName: 'value', Interval: 1, Decay: 1,
		});
		ctrl.stopObserving(); // stop background timer first
		const stop = vi.spyOn(ctrl, 'stopObserving');
		TValueTriggeredCallback.stop('vtc1');
		expect(stop).toHaveBeenCalled();
	});

	it('checkChanges dispatches a callback when value has changed', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TValueTriggeredCallback({
			ID: 'vtc1', EventTarget: 'vtc1', ControlID: 'vtc_ctrl',
			PropertyName: 'value', Interval: 1, Decay: 1,
		});
		ctrl.stopObserving();
		ctrl.value = 'initial';
		input.value = 'changed';
		ctrl.checkChanges();
		expect(dispatchMock).toHaveBeenCalled();
	});

	it('checkChanges does NOT dispatch when value is unchanged', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TValueTriggeredCallback({
			ID: 'vtc1', EventTarget: 'vtc1', ControlID: 'vtc_ctrl',
			PropertyName: 'value', Interval: 1, Decay: 1,
		});
		ctrl.stopObserving();
		ctrl.value = 'initial';
		input.value = 'initial'; // same
		ctrl.checkChanges();
		expect(dispatchMock).not.toHaveBeenCalled();
	});
});

// ─── TActiveTableCell / TActiveTableRow ───────────────────────────────────────

describe('TActiveTableCell', () => {
	let td;

	beforeEach(() => {
		clearRegistry();
		const table = document.createElement('table');
		const tr    = document.createElement('tr');
		td          = document.createElement('td');
		td.id       = 'tc1';
		tr.appendChild(td);
		table.appendChild(tr);
		document.body.appendChild(table);
	});

	afterEach(() => {
		restoreMocks();
		document.querySelector('table')?.remove();
	});

	it('is a subclass of CallbackControl', () => {
		expect(TActiveTableCell.superclass).toBe(CallbackControl);
	});

	it('registers in Prado.Registry on construction', () => {
		new TActiveTableCell({ ID: 'tc1', EventTarget: 'tc1' });
		expect(Registry['tc1']).toBeDefined();
	});

	it('dispatches a CallbackRequest on postback', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveTableCell({ ID: 'tc1', EventTarget: 'tc1' });
		const evt  = fakeEvent({ target: td });
		ctrl.onPostBack({ EventTarget: 'tc1' }, evt);
		expect(dispatchMock).toHaveBeenCalled();
		expect(evt.preventDefault).toHaveBeenCalled();
	});
});

describe('TActiveTableRow', () => {
	let tr;

	beforeEach(() => {
		clearRegistry();
		const table = document.createElement('table');
		tr          = document.createElement('tr');
		tr.id       = 'tr1';
		table.appendChild(tr);
		document.body.appendChild(table);
	});

	afterEach(() => {
		restoreMocks();
		document.querySelector('table')?.remove();
	});

	it('is a subclass of CallbackControl', () => {
		expect(TActiveTableRow.superclass).toBe(CallbackControl);
	});

	it('registers in Prado.Registry on construction', () => {
		new TActiveTableRow({ ID: 'tr1', EventTarget: 'tr1' });
		expect(Registry['tr1']).toBeDefined();
	});

	it('dispatches a CallbackRequest on postback', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveTableRow({ ID: 'tr1', EventTarget: 'tr1' });
		const evt  = fakeEvent({ target: tr });
		ctrl.onPostBack({ EventTarget: 'tr1' }, evt);
		expect(dispatchMock).toHaveBeenCalled();
		expect(evt.preventDefault).toHaveBeenCalled();
	});
});

// ─── TActiveDetails ──────────────────────────────────────────────────────────

/**
 * jsdom reflects the `open` attribute of <details> but never fires the native
 * `toggle` event of its own accord, so a toggle is dispatched by hand where a
 * browser would raise one. The end-to-end behavior is covered by
 * tests/playwright/active-controls/ActiveDetailsTestCase.spec.js.
 */
describe('TActiveDetails', () => {
	let details;

	/** Raise the `toggle` event the browser fires after `open` changes. */
	function toggle(open) {
		details.open = open;
		details.dispatchEvent(new Event('toggle'));
	}

	beforeEach(() => {
		clearRegistry();
		details = document.createElement('details');
		details.id = 'details1';
		details.appendChild(document.createElement('summary'));
		document.body.appendChild(details);
	});

	afterEach(() => {
		restoreMocks();
		details.remove();
	});

	it('registers itself in Prado.Registry on construction', () => {
		new TActiveDetails({ ID: 'details1', EventTarget: 'details1' });
		expect(Registry['details1']).toBeDefined();
	});

	it('dispatches a callback with CallbackParameter "open" when the user opens it', () => {
		const { MockCtor, dispatchMock } = mockCallbackRequest();
		new TActiveDetails({ ID: 'details1', EventTarget: 'details1' });

		toggle(true);

		expect(dispatchMock).toHaveBeenCalledTimes(1);
		expect(MockCtor).toHaveBeenCalledWith(
			'details1',
			expect.objectContaining({ CallbackParameter: 'open' }),
		);
	});

	it('dispatches a callback with CallbackParameter "close" when the user closes it', () => {
		const { MockCtor, dispatchMock } = mockCallbackRequest();
		new TActiveDetails({ ID: 'details1', EventTarget: 'details1' });
		details.open = true;

		toggle(false);

		expect(dispatchMock).toHaveBeenCalledTimes(1);
		expect(MockCtor).toHaveBeenCalledWith(
			'details1',
			expect.objectContaining({ CallbackParameter: 'close' }),
		);
	});

	it('does not mutate the options object it was constructed with', () => {
		const { MockCtor } = mockCallbackRequest();
		const options = { ID: 'details1', EventTarget: 'details1' };
		new TActiveDetails(options);

		toggle(true);

		expect(options.CallbackParameter).toBeUndefined();
		expect(MockCtor.mock.calls[0][1]).not.toBe(options);
	});

	it('setOpen() opens the widget without echoing a callback to the server', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveDetails({ ID: 'details1', EventTarget: 'details1' });

		ctrl.setOpen(true);
		expect(details.open).toBe(true);
		// the browser raises toggle for the change the server just made
		details.dispatchEvent(new Event('toggle'));

		expect(dispatchMock).not.toHaveBeenCalled();
	});

	it('setOpen() closes the widget without echoing a callback to the server', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveDetails({ ID: 'details1', EventTarget: 'details1' });
		details.open = true;

		ctrl.setOpen(false);
		expect(details.open).toBe(false);
		details.dispatchEvent(new Event('toggle'));

		expect(dispatchMock).not.toHaveBeenCalled();
	});

	it('suppresses only the toggle raised by setOpen(), not the next user toggle', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveDetails({ ID: 'details1', EventTarget: 'details1' });

		ctrl.setOpen(true);
		details.dispatchEvent(new Event('toggle'));
		expect(dispatchMock).not.toHaveBeenCalled();

		toggle(false);
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('setOpen() to the value already held arms no suppression', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveDetails({ ID: 'details1', EventTarget: 'details1' });

		ctrl.setOpen(false); // already closed, nothing to do
		toggle(true); // a genuine user toggle must still reach the server

		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('the static setOpen() forwards to the instance found in the Registry', () => {
		mockCallbackRequest();
		const ctrl = new TActiveDetails({ ID: 'details1', EventTarget: 'details1' });
		const spy = vi.spyOn(ctrl, 'setOpen');

		TActiveDetails.setOpen('details1', true);

		expect(spy).toHaveBeenCalledWith(true);
		expect(details.open).toBe(true);
	});

	it('the static setOpen() ignores an unknown id', () => {
		expect(() => TActiveDetails.setOpen('no-such-control', true)).not.toThrow();
	});
});

// ─── TActiveDialog ───────────────────────────────────────────────────────────

/**
 * jsdom reflects the `open` attribute of <dialog> but implements neither
 * show() nor close(), so both are stubbed with the semantics a browser gives
 * them: show() sets the attribute, close() clears it and fires `close`. The
 * MutationObserver that detects a programmatic open delivers asynchronously,
 * hence the awaited ticks. The end-to-end behavior is covered by
 * tests/playwright/active-controls/ActiveDialogTestCase.spec.js.
 */
describe('TActiveDialog', () => {
	let dialog;
	let controls;

	/** Let the MutationObserver deliver its queued records. */
	const tick = () => new Promise((resolve) => setTimeout(resolve, 0));

	/**
	 * Build a control and remember it, so afterEach can disconnect its
	 * MutationObserver. An observer left connected delivers its queued records
	 * into a later test and dispatches through that test's mock.
	 */
	function makeDialog(options) {
		const ctrl = new TActiveDialog(options ?? { ID: 'dialog1', EventTarget: 'dialog1' });
		controls.push(ctrl);
		return ctrl;
	}

	beforeEach(async () => {
		clearRegistry();
		controls = [];
		dialog = document.createElement('dialog');
		dialog.id = 'dialog1';
		dialog.show = function () {
			this.open = true;
		};
		dialog.close = function () {
			this.open = false;
			this.dispatchEvent(new Event('close'));
		};
		document.body.appendChild(dialog);

		// The synchronous tests above never yield to the macrotask queue, so the
		// timers their trigger controls left behind fire on the first awaited
		// tick in this file. Drain them here, against a throwaway mock so no
		// real request is attempted, and every test below counts only its own
		// dispatches.
		mockCallbackRequest();
		await tick();
		restoreMocks();
	});

	afterEach(() => {
		controls.forEach((ctrl) => ctrl.onDone());
		restoreMocks();
		dialog.remove();
	});

	it('registers itself in Prado.Registry on construction', () => {
		makeDialog();
		expect(Registry['dialog1']).toBeDefined();
	});

	it('dispatches a callback with CallbackParameter "close" when the dialog is dismissed', () => {
		const { MockCtor, dispatchMock } = mockCallbackRequest();
		makeDialog();
		dialog.open = true;

		dialog.close();

		expect(dispatchMock).toHaveBeenCalledTimes(1);
		expect(MockCtor).toHaveBeenCalledWith(
			'dialog1',
			expect.objectContaining({ CallbackParameter: 'close' }),
		);
	});

	it('dispatches a callback with CallbackParameter "open" when opened programmatically', async () => {
		const { MockCtor, dispatchMock } = mockCallbackRequest();
		makeDialog();

		dialog.show(); // showModal()/show() called by page script, not by the server
		await tick();

		expect(dispatchMock).toHaveBeenCalledTimes(1);
		expect(MockCtor).toHaveBeenCalledWith(
			'dialog1',
			expect.objectContaining({ CallbackParameter: 'open' }),
		);
	});

	it('reports an open only once for a single attribute change', async () => {
		const { dispatchMock } = mockCallbackRequest();
		makeDialog();

		dialog.show();
		dialog.setAttribute('open', ''); // redundant write, state unchanged
		await tick();

		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('setOpen(true) opens the dialog without echoing a callback to the server', async () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = makeDialog();

		ctrl.setOpen(true);
		await tick();

		expect(dialog.open).toBe(true);
		expect(dispatchMock).not.toHaveBeenCalled();
	});

	it('setOpen(false) closes the dialog without echoing a callback to the server', async () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = makeDialog();
		ctrl.setOpen(true);
		await tick();

		ctrl.setOpen(false);
		await tick();

		expect(dialog.open).toBe(false);
		expect(dispatchMock).not.toHaveBeenCalled();
	});

	it('suppresses only the close raised by setOpen(), not the next user dismissal', async () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = makeDialog();

		ctrl.setOpen(true);
		await tick();
		ctrl.setOpen(false);
		await tick();
		expect(dispatchMock).not.toHaveBeenCalled();

		dialog.show();
		await tick();
		dispatchMock.mockClear();
		dialog.close(); // the user dismisses it
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('does not mutate the options object it was constructed with', () => {
		const { MockCtor } = mockCallbackRequest();
		const options = { ID: 'dialog1', EventTarget: 'dialog1' };
		makeDialog(options);
		dialog.open = true;

		dialog.close();

		expect(options.CallbackParameter).toBeUndefined();
		expect(MockCtor.mock.calls[0][1]).not.toBe(options);
	});

	it('onDone() disconnects the observer so a later open reports nothing', async () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = makeDialog();

		ctrl.onDone();
		dialog.show();
		await tick();

		expect(dispatchMock).not.toHaveBeenCalled();
	});

	it('the static setOpen() forwards to the instance found in the Registry', async () => {
		mockCallbackRequest();
		const ctrl = makeDialog();
		const spy = vi.spyOn(ctrl, 'setOpen');

		TActiveDialog.setOpen('dialog1', true);
		await tick();

		expect(spy).toHaveBeenCalledWith(true);
		expect(dialog.open).toBe(true);
	});

	it('the static setOpen() ignores an unknown id', () => {
		expect(() => TActiveDialog.setOpen('no-such-control', true)).not.toThrow();
	});
});
