/*! PRADO TUnsavedChangesGuard javascript file | github.com/pradosoft/prado */

/**
 * TUnsavedChangesGuard client class.
 *
 * The guard watches the fields of its scope: the element `ScopeID` names, or the form
 * that holds the guard element. A field is changed while its state differs from its
 * default, which is the state the server rendered:
 *
 * | Field | Changed when |
 * |---|---|
 * | text-like input, textarea | `value` differs from the sanitized `defaultValue` |
 * | checkbox, radio | `checked` differs from `defaultChecked` |
 * | select | the selected options differ from the `defaultSelected` ones |
 * | file | the chosen files differ from the baseline (none) |
 *
 * Hidden, button, submit, reset, and image inputs are not watched, nor is a field
 * inside an element with `data-prado-dirty-ignore`, such as the container of the
 * Prado state fields. Typing a change and undoing it clears the mark.
 *
 * A changed field carries `data-prado-dirty` and the `DirtyCssClass`; a radio carries
 * them only while it is the checked one. The scope carries `data-prado-unsaved` while
 * any field is changed or {@link markDirty} is in force, and each change of that state
 * dispatches `prado:dirtyChange` on the scope with `detail.dirty`. With `StatusText`,
 * the guard element is a `role="status"` live region that holds the text while the
 * scope is dirty and is empty while it is clean.
 *
 * With `WarnOnLeave`, a `beforeunload` listener is installed only while the scope is
 * dirty, so the browser asks before the page unloads. A submission of the guard's form
 * in this window fires `formdata` and is not a leave, so Prado postbacks, buttons, and
 * auto-postbacks pass, as does a callback redirect (`prado:callbackRedirect`). A
 * cancelled submission fires no `formdata`, and a submission to another window keeps
 * the page.
 *
 * The rendered defaults are the baseline, so a postback or a callback that re-renders
 * fields resets it. {@link markClean} takes the current state as the baseline; the
 * server calls it through `TUnsavedChangesGuard::markClean()` after a callback saves.
 * The guard re-checks the fields after each callback response (`prado:callbackUpdated`),
 * on a form reset, and when the page returns from the back-forward cache. A callback
 * creates the wrapper again, which keeps the {@link markDirty} state.
 *
 * ```javascript
 * const guard = Prado.Registry['ctl0_Content_Guard'];
 * guard.isDirty();       // whether the scope has unsaved changes
 * guard.dirtyFields();   // the changed fields
 * guard.markClean();     // the current state becomes the baseline
 * Prado.WebUI.TUnsavedChangesGuard.markClean('ctl0_Content_Guard');
 * ```
 */
Prado.WebUI.TUnsavedChangesGuard = Prado.Class(Prado.WebUI.Control,
{
	/**
	 * A callback that renders the guard again creates a new wrapper; it keeps the
	 * {@link markDirty} state and the file baselines of the wrapper it replaces.
	 * @param {function} $super - the parent replace()
	 * @param {object} oldwrapper - the replaced wrapper
	 * @param {object} options - the new options
	 * @returns {object} the registered wrapper
	 */
	replace($super, oldwrapper, options)
	{
		this.inherited = { forced: !!oldwrapper.forced, fileBaselines: oldwrapper.fileBaselines };
		return $super(oldwrapper, options);
	},

	onInit(options)
	{
		this.options = options || {};
		this.scope = this.options.ScopeID
			? document.getElementById(this.options.ScopeID)
			: (this.element ? this.element.closest('form') : null);
		this.form = this.scope ? this.scope.closest('form') : null;
		const inherited = this.inherited || {};
		this.forced = !!this.options.Dirty || !!inherited.forced;
		this.dirty = false;
		this.synced = false;
		this.leaving = false;
		this.leaveTask = false;
		this.submitter = null;
		this.fileBaselines = inherited.fileBaselines || new WeakMap();
		this.defaults = new WeakMap();
		this.unloadHandler = this.onBeforeUnload.bind(this);
		this.unloadInstalled = false;
		this.captures = [];
		if (!this.scope)
			return;
		this.dirty = this.scope.hasAttribute('data-prado-unsaved');
		const edited = this.onEdit.bind(this);
		this.capture(this.scope, 'input', edited);
		this.capture(this.scope, 'change', edited);
		if (this.form) {
			this.observe(this.form, 'submit', this.onSubmit.bind(this));
			this.observe(this.form, 'formdata', this.onFormData.bind(this));
			this.observe(this.form, 'reset', () => this.setTimeout(() => this.update(), 0));
		}
		this.observe(document, 'prado:callbackRedirect', () => this.leave());
		this.observe(document, 'prado:callbackUpdated', () => this.update());
		this.observe(window, 'pageshow', () => { this.leaving = false; this.update(); });
		this.update();
	},

	onDone()
	{
		for (const c of this.captures)
			c.target.removeEventListener(c.type, c.handler, true);
		this.captures = [];
		this.installUnload(false);
	},

	/**
	 * Adds a capturing listener, removed in onDone(). A capturing listener on the scope
	 * runs before a field's own change handler, which may post back.
	 * @param {EventTarget} target - the element to listen on
	 * @param {string} type - the event type
	 * @param {function} handler - the listener
	 */
	capture(target, type, handler)
	{
		target.addEventListener(type, handler, true);
		this.captures.push({ target, type, handler });
	},

	/**
	 * Re-checks the fields. An edit after the task of a submission ends that leave, as
	 * the page stayed; a checkbox posts back on click, before its own input and change.
	 */
	onEdit()
	{
		if (!this.leaveTask)
			this.leaving = false;
		this.update();
	},

	/**
	 * Records the submitter for {@link onFormData}.
	 * @param {SubmitEvent} event - the submit event
	 */
	onSubmit(event)
	{
		this.submitter = event.submitter || null;
	},

	/**
	 * A submission of the form in this window is a leave the guard does not warn about.
	 * @param {Event} event - the formdata event
	 */
	onFormData(event)
	{
		if (event.target !== this.form)
			return;
		const submitter = this.submitter;
		this.submitter = null;
		const target = (submitter && submitter.hasAttribute('formtarget'))
			? submitter.getAttribute('formtarget')
			: this.form.getAttribute('target');
		if (target && !['_self', '_top', '_parent'].includes(target.toLowerCase()))
			return;
		this.leave();
	},

	/**
	 * Marks the next unload as one the guard does not warn about.
	 */
	leave()
	{
		this.leaving = true;
		this.leaveTask = true;
		window.setTimeout(() => { this.leaveTask = false; }, 0);
	},

	/**
	 * Asks the browser to confirm the unload while the scope is dirty.
	 * @param {BeforeUnloadEvent} event - the beforeunload event
	 */
	onBeforeUnload(event)
	{
		if (this.leaving) {
			this.leaving = false;
			return;
		}
		if (!this.dirty)
			return;
		event.preventDefault();
		event.returnValue = '';
	},

	/**
	 * @returns {boolean} whether the scope has unsaved changes.
	 */
	isDirty()
	{
		return this.dirty;
	},

	/**
	 * @returns {Element[]} the watched fields of the scope.
	 */
	fields()
	{
		if (!this.scope)
			return [];
		const list = this.scope.tagName === 'FORM'
			? Array.from(this.scope.elements)
			: Array.from(this.scope.querySelectorAll('input, select, textarea'));
		return list.filter((el) => this.isWatched(el));
	},

	/**
	 * @param {Element} el - a form control
	 * @returns {boolean} whether the guard watches the control.
	 */
	isWatched(el)
	{
		if (el.tagName === 'INPUT') {
			if (Prado.WebUI.TUnsavedChangesGuard.UNWATCHED_TYPES.includes(el.type))
				return false;
		} else if (el.tagName !== 'SELECT' && el.tagName !== 'TEXTAREA') {
			return false;
		}
		return !el.closest('[data-prado-dirty-ignore]');
	},

	/**
	 * @returns {Element[]} the marked fields; a changed radio group lists its checked radio.
	 */
	dirtyFields()
	{
		return this.fields().filter((el) => this.isFieldDirty(el) && this.isMarkable(el));
	},

	/**
	 * @param {Element} el - a changed field
	 * @returns {boolean} whether the field carries the mark; a radio does while checked.
	 */
	isMarkable(el)
	{
		return el.type !== 'radio' || el.checked;
	},

	/**
	 * @param {Element} el - a watched field
	 * @returns {boolean} whether the field differs from its baseline.
	 */
	isFieldDirty(el)
	{
		if (el.tagName === 'SELECT')
			return this.isSelectDirty(el);
		if (el.tagName === 'TEXTAREA')
			return this.normalize(el.value) !== this.normalize(el.defaultValue);
		switch (el.type) {
			case 'checkbox':
			case 'radio':
				return el.checked !== el.defaultChecked;
			case 'file':
				return !this.sameFiles(el.files, this.fileBaselines.get(el));
			default:
				return el.value !== this.defaultValueOf(el);
		}
	},

	/**
	 * A single-choice select of display size 1 without a `selected` option starts on its
	 * first enabled option; the last `selected` option wins when several have it.
	 * @param {HTMLSelectElement} el - the select
	 * @returns {boolean} whether its selection differs from the default one.
	 */
	isSelectDirty(el)
	{
		const options = Array.from(el.options);
		if (el.multiple)
			return options.some((o) => o.selected !== o.defaultSelected);
		let index = -1;
		options.forEach((o, i) => {
			if (o.defaultSelected)
				index = i;
		});
		if (index === -1 && el.size <= 1)
			index = options.findIndex((o) => !o.disabled && !(o.parentElement.tagName === 'OPTGROUP' && o.parentElement.disabled));
		return el.selectedIndex !== index;
	},

	/**
	 * The browser sanitizes an input's value, so a range without a value starts at its
	 * midpoint and a color is lower case. A detached clone sanitizes the default.
	 * @param {HTMLInputElement} el - the input
	 * @returns {string} the sanitized default value.
	 */
	defaultValueOf(el)
	{
		const raw = el.defaultValue;
		const cached = this.defaults.get(el);
		if (cached && cached.raw === raw && cached.type === el.type)
			return cached.value;
		const probe = el.cloneNode(false);
		probe.value = raw;
		const value = probe.value;
		this.defaults.set(el, { raw, type: el.type, value });
		return value;
	},

	/**
	 * @param {string} text - a textarea value
	 * @returns {string} the text with LF line breaks.
	 */
	normalize(text)
	{
		return String(text).replace(/\r\n?/g, '\n');
	},

	/**
	 * @param {?FileList} files - the chosen files
	 * @param {?File[]} baseline - the files of the baseline
	 * @returns {boolean} whether both hold the same files.
	 */
	sameFiles(files, baseline)
	{
		const list = files ? Array.from(files) : [];
		const base = baseline || [];
		return list.length === base.length && list.every((f, i) => f === base[i]);
	},

	/**
	 * Re-checks the fields, marks the changed ones, and updates the scope state.
	 * @returns {boolean} whether the scope is dirty.
	 */
	update()
	{
		if (!this.scope)
			return false;
		const stale = new Set(this.scope.querySelectorAll('[data-prado-dirty]'));
		let changed = false;
		for (const el of this.fields()) {
			if (!this.isFieldDirty(el))
				continue;
			changed = true;
			if (this.isMarkable(el)) {
				this.mark(el, true);
				stale.delete(el);
			}
		}
		for (const el of stale)
			this.mark(el, false);
		this.setDirty(changed || this.forced);
		return this.dirty;
	},

	/**
	 * @param {Element} el - a field
	 * @param {boolean} on - whether to add or remove the mark
	 */
	mark(el, on)
	{
		el.toggleAttribute('data-prado-dirty', on);
		for (const name of Prado.Element.classTokens(this.options.DirtyCssClass))
			el.classList.toggle(name, on);
	},

	/**
	 * Applies the scope state. The first call applies it in full, because a wrapper
	 * that replaces another starts from the markup that one left.
	 * @param {boolean} dirty - whether the scope is dirty
	 */
	setDirty(dirty)
	{
		if (dirty === this.dirty && this.synced)
			return;
		const changed = dirty !== this.dirty;
		this.dirty = dirty;
		this.synced = true;
		this.scope.toggleAttribute('data-prado-unsaved', dirty);
		if (this.element && this.options.StatusText)
			this.element.textContent = dirty ? this.options.StatusText : '';
		this.installUnload(dirty && this.options.WarnOnLeave !== false);
		if (changed)
			this.scope.dispatchEvent(new CustomEvent('prado:dirtyChange', { bubbles: true, detail: { dirty } }));
	},

	/**
	 * Installs the beforeunload listener only while it is needed, because an
	 * installed listener keeps a page out of the back-forward cache in some browsers.
	 * @param {boolean} on - whether to install or remove it
	 */
	installUnload(on)
	{
		if (on === this.unloadInstalled)
			return;
		this.unloadInstalled = on;
		if (on)
			window.addEventListener('beforeunload', this.unloadHandler);
		else
			window.removeEventListener('beforeunload', this.unloadHandler);
	},

	/**
	 * Takes the current state of the fields as the baseline and ends {@link markDirty}.
	 * The defaults are written back to the fields, so a form reset returns to this state.
	 */
	markClean()
	{
		for (const el of this.fields()) {
			if (el.tagName === 'SELECT') {
				for (const o of el.options)
					o.defaultSelected = o.selected;
			} else if (el.type === 'checkbox' || el.type === 'radio') {
				el.defaultChecked = el.checked;
			} else if (el.type === 'file') {
				this.fileBaselines.set(el, Array.from(el.files || []));
			} else {
				el.defaultValue = el.value;
			}
		}
		this.forced = false;
		this.update();
	},

	/**
	 * Keeps the scope dirty, whatever the fields hold, until {@link markClean}.
	 */
	markDirty()
	{
		this.forced = true;
		this.update();
	}
});

Object.assign(Prado.WebUI.TUnsavedChangesGuard,
{
	/**
	 * Input types the guard does not watch.
	 */
	UNWATCHED_TYPES: ['hidden', 'button', 'submit', 'reset', 'image'],

	/**
	 * Takes the current state as the baseline of the guard with the ID.
	 * @param {string} id - the guard ClientID
	 */
	markClean(id)
	{
		const guard = Prado.Registry[id];
		if (guard)
			guard.markClean();
	},

	/**
	 * Keeps the scope of the guard with the ID dirty until it is marked clean.
	 * @param {string} id - the guard ClientID
	 */
	markDirty(id)
	{
		const guard = Prado.Registry[id];
		if (guard)
			guard.markDirty();
	}
});
