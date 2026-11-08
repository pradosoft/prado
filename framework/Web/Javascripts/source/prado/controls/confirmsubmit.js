/*! PRADO TConfirmSubmit javascript file | github.com/pradosoft/prado */

/**
 * TConfirmSubmit control.
 *
 * A capture listener on the wrapper stops a user action before a control inside it
 * sees the event, and opens the confirmation dialog. OK sends the event again, so the
 * control's own handlers run: validation, a postback, a callback, or a navigation.
 * Cancel, Esc, and a click outside the dialog drop the action.
 *
 * - A click on a link, button, or image map area, or on a checkbox or radio button
 *   with AutoPostBack, is cancelled, so the checked state stays until OK.
 * - A change of a list or text box with AutoPostBack has already altered the value,
 *   so the control records the value on focus and restores it on a drop.
 * - Enter in such a text box is the key that opens the dialog. Its default action is
 *   cancelled, so the rest of the key press does not activate the focused Cancel
 *   button or submit the form.
 */
Prado.WebUI.TConfirmSubmit = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.dialog = document.getElementById(options.DialogID);
		if (!this.element || !this.dialog)
			return;
		this.autoPostBack = options.AutoPostBack || [];
		this.values = new WeakMap();
		this.pending = null;
		this.resuming = false;
		this.onCapture = {
			click: (event) => this.onClick(event),
			change: (event) => this.onChange(event),
			focusin: (event) => this.record(event.target),
			keydown: (event) => this.onKeyDown(event),
		};
		for (const type of Object.keys(this.onCapture))
			this.element.addEventListener(type, this.onCapture[type], true);
		this.observe(options.OkID, 'click', () => this.confirm());
		this.observe(this.dialog, 'close', () => this.drop());
	},

	/**
	 * @param {Element} el - an element in the wrapper
	 * @returns {boolean} whether a control with AutoPostBack is the element or holds it
	 */
	isAutoPostBack(el) {
		return this.autoPostBack.some((id) => {
			const control = document.getElementById(id);
			return el.id === id || el.id.startsWith(id + '_') || (control && control.contains(el));
		});
	},

	/**
	 * @param {EventTarget} target - the target of a click
	 * @returns {?Element} the element the click acts on, when it needs confirmation
	 */
	findClickItem(target) {
		const item = target.closest(Prado.WebUI.TConfirmSubmit.CLICK_ITEMS);
		if (!item || !this.element.contains(item) || item.disabled)
			return null;
		if (item.matches('input[type="checkbox"], input[type="radio"]') && !this.isAutoPostBack(item))
			return null;
		return item;
	},

	/**
	 * Holds a click and asks for confirmation.
	 * @param {MouseEvent} event - the click event
	 */
	onClick(event) {
		if (this.resuming)
			return;
		const item = this.findClickItem(event.target);
		if (!item)
			return;
		event.preventDefault();
		event.stopPropagation();
		const init = {
			bubbles: true, cancelable: true, view: event.view, detail: event.detail, button: event.button,
			screenX: event.screenX, screenY: event.screenY, clientX: event.clientX, clientY: event.clientY,
			ctrlKey: event.ctrlKey, shiftKey: event.shiftKey, altKey: event.altKey, metaKey: event.metaKey,
		};
		this.ask(() => item.dispatchEvent(new MouseEvent('click', init)), null);
	},

	/**
	 * Holds the change of a control with AutoPostBack and asks for confirmation.
	 * @param {Event} event - the change event
	 */
	onChange(event) {
		const el = event.target;
		if (this.resuming || !Prado.WebUI.TConfirmSubmit.isValueField(el) || !this.isAutoPostBack(el))
			return;
		event.stopPropagation();
		this.ask(() => {
			this.record(el);
			el.dispatchEvent(new Event('change', { bubbles: true }));
		}, () => this.restore(el));
	},

	/**
	 * Cancels the default action of Enter in a text box with AutoPostBack. The
	 * control's handler still runs and sends the change that opens the dialog.
	 * @param {KeyboardEvent} event - the keydown event
	 */
	onKeyDown(event) {
		const el = event.target;
		if (event.key === 'Enter' && !this.resuming && Prado.WebUI.TConfirmSubmit.isValueField(el) && el.tagName === 'INPUT' && this.isAutoPostBack(el))
			event.preventDefault();
	},

	/**
	 * Records the value of a field, the one a dropped change returns to.
	 * @param {EventTarget} el - the focused element
	 */
	record(el) {
		if (this.pending || !Prado.WebUI.TConfirmSubmit.isValueField(el))
			return;
		this.values.set(el, el.tagName === 'SELECT' ? Array.from(el.options, (option) => option.selected) : el.value);
	},

	/**
	 * Returns a field to its recorded value.
	 * @param {Element} el - the field
	 */
	restore(el) {
		if (!this.values.has(el))
			return;
		const value = this.values.get(el);
		if (el.tagName === 'SELECT')
			Array.from(el.options).forEach((option, index) => { option.selected = value[index]; });
		else
			el.value = value;
	},

	/**
	 * Opens the dialog for an action.
	 * @param {Function} resume - runs the action on OK
	 * @param {?Function} cancel - runs when the action is dropped
	 */
	ask(resume, cancel) {
		if (this.dialog.open)
			return;
		this.drop();
		this.pending = { resume, cancel };
		this.dialog.showModal();
	},

	/**
	 * Closes the dialog and resumes the action. The action runs after the close, so
	 * the page is not inert, and the focus has returned to the control.
	 */
	confirm() {
		const pending = this.pending;
		this.pending = null;
		this.dialog.close();
		if (!pending)
			return;
		this.resuming = true;
		try {
			pending.resume();
		} finally {
			this.resuming = false;
		}
	},

	/**
	 * Drops the action of a dialog closed by Cancel, Esc, or a click outside. The
	 * `close` event fires a task after the close, so ask() drops an earlier action
	 * itself, and a late event finds the dialog open for the next action and is ignored.
	 */
	drop() {
		if (this.dialog.open)
			return;
		const pending = this.pending;
		this.pending = null;
		if (pending && pending.cancel)
			pending.cancel();
	},

	onDone() {
		for (const type of Object.keys(this.onCapture || {}))
			this.element.removeEventListener(type, this.onCapture[type], true);
	}
});

/**
 * The elements a click on acts on.
 */
Prado.WebUI.TConfirmSubmit.CLICK_ITEMS = 'a[href], area[href], button, input[type="submit"], input[type="image"], input[type="button"], input[type="reset"], input[type="checkbox"], input[type="radio"]';

/**
 * @param {EventTarget} el - an event target
 * @returns {boolean} whether the element holds a value that a change alters: a list,
 *   a text area, or an input other than a checkbox, radio button, or button
 */
Prado.WebUI.TConfirmSubmit.isValueField = function(el) {
	return el.matches('select, textarea, input:not([type="checkbox"], [type="radio"], [type="submit"], [type="image"], [type="button"], [type="reset"], [type="file"], [type="hidden"])');
};
