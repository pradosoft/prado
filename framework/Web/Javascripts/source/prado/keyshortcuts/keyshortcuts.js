/*! PRADO keyboard shortcuts javascript file | github.com/pradosoft/prado */

/**
 * Prado.KeyShortcuts: the key-binding registry.
 *
 * One document `keydown` listener matches each key press against two kinds of binding:
 *
 * - Declared elements: an element with `aria-keyshortcuts` and the
 *   `data-prado-keyshortcuts` marker. TWebControl.KeyShortcuts renders both. The
 *   marker value is the ID of the scope element, or '' for a page-wide binding.
 *   `data-prado-keyshortcuts-reserved` opts in to reserved shortcuts. The DOM is the
 *   source of truth, so an element replaced or morphed by a callback keeps working
 *   and a removed element stops.
 * - Registrations: `Prado.KeyShortcuts.register(keys, action, options)`.
 *
 * ```javascript
 * const remove = Prado.KeyShortcuts.register('Control+K Meta+K', (event, shortcut) => openSearch());
 * Prado.KeyShortcuts.register('B', 'ctl0_Content_Bold', { scope: 'ctl0_Content_Toolbar' });
 * remove();
 * ```
 *
 * Activation: a shortcut on a button, link, summary, check box, radio button, or an
 * element with a button-like role clicks it. Any other element is focused. The element
 * first receives a cancelable, bubbling `prado:keyshortcut` event whose `detail` holds
 * `shortcut` and the keyboard `event`; cancelling it skips the click or focus.
 *
 * A key press is ignored when:
 * - another listener already called `preventDefault()`, or IME composition is in progress;
 * - AltGraph is held, since it types characters;
 * - focus is in an editable field and the shortcut has no Control, Alt, or Meta
 *   modifier and is not a function key;
 * - the shortcut is reserved (see `isReserved()`) and the binding did not opt in.
 *
 * A binding is skipped when its element is disabled, inert, hidden, or disconnected; when
 * focus is outside its scope element; or when a modal dialog is open and its element (or
 * scope, for a function) is outside the dialog. Character-key shortcuts that work
 * page-wide are skipped while `setCharacterKeysEnabled(false)` is in effect (WCAG 2.1.4).
 *
 * Precedence, for bindings of the same shortcut:
 * | Rule | Result |
 * |---|---|
 * | scoped vs page-wide | the binding with the deepest scope element containing focus wins |
 * | same scope depth | registrations win over declared elements; a later registration wins |
 * | declared elements | the first in document order wins |
 *
 * Matching uses `event.key`. When the key is not an ASCII letter or digit (another
 * layout, a macOS Option character, or a shifted digit), the physical `event.code`
 * letter or digit also matches. A shifted symbol also matches without Shift, so `?`
 * matches Shift+/. Control and Meta are distinct: list 'Control+S Meta+S' for both
 * Windows and macOS.
 */
if (!Prado.KeyShortcuts) {
	Prado.KeyShortcuts = {
		/** Modifier keys in normalized order. */
		MODIFIERS: ['Control', 'Alt', 'Shift', 'Meta'],

		/** Attribute that marks a declared element; its value is the scope element ID. */
		MARKER: 'data-prado-keyshortcuts',

		/** Attribute that opts a declared element in to reserved shortcuts. */
		RESERVED_MARKER: 'data-prado-keyshortcuts-reserved',

		/** localStorage key holding the character-key setting. */
		STORAGE_KEY: 'prado:keyshortcuts:characterKeys',

		_modifierNames: {
			control: 'Control', ctrl: 'Control', alt: 'Alt', option: 'Alt', shift: 'Shift',
			meta: 'Meta', cmd: 'Meta', command: 'Meta',
		},

		_keyNames: {
			space: 'Space', spacebar: 'Space', plus: 'Plus', enter: 'Enter', return: 'Enter',
			escape: 'Escape', esc: 'Escape', tab: 'Tab', backspace: 'Backspace', delete: 'Delete',
			del: 'Delete', insert: 'Insert', ins: 'Insert', home: 'Home', end: 'End',
			pageup: 'PageUp', pagedown: 'PageDown', arrowup: 'ArrowUp', up: 'ArrowUp',
			arrowdown: 'ArrowDown', down: 'ArrowDown', arrowleft: 'ArrowLeft', left: 'ArrowLeft',
			arrowright: 'ArrowRight', right: 'ArrowRight',
		},

		// event.key values that name a key differently than the shortcut syntax.
		_eventKeyNames: {
			' ': 'Space', '+': 'Plus', Spacebar: 'Space', Esc: 'Escape', Del: 'Delete',
			Up: 'ArrowUp', Down: 'ArrowDown', Left: 'ArrowLeft', Right: 'ArrowRight',
		},

		_modifierKeys: new Set(['Control', 'Alt', 'Shift', 'Meta', 'AltGraph', 'CapsLock', 'Fn', 'FnLock', 'Hyper', 'OS', 'Super', 'Symbol', 'NumLock', 'ScrollLock']),

		_textInputExceptions: new Set(['button', 'submit', 'reset', 'image', 'checkbox', 'radio', 'file', 'color', 'hidden']),

		_clickInputs: new Set(['button', 'submit', 'reset', 'image', 'checkbox', 'radio']),

		_clickRoles: new Set(['button', 'link', 'checkbox', 'radio', 'switch', 'menuitem', 'menuitemcheckbox', 'menuitemradio', 'tab', 'option']),

		_editableRoles: new Set(['textbox', 'searchbox', 'combobox', 'spinbutton']),

		_bindings: [],

		_parsed: new WeakMap(),

		_characterKeys: null,

		_reserved: null,

		/**
		 * Normalizes one key token.
		 * @param {string} token - a key token
		 * @returns {?string} the key value, or null for an unknown key name
		 */
		normalizeKey(token) {
			if ([...token].length === 1) {
				const upper = token.toUpperCase();
				return [...upper].length === 1 ? upper : token;
			}
			const lower = token.toLowerCase();
			if (Object.hasOwn(this._keyNames, lower)) {
				return this._keyNames[lower];
			}
			const match = /^f([1-9]|1[0-9]|2[0-4])$/.exec(lower);
			return match ? 'F' + match[1] : null;
		},

		/**
		 * Normalizes one shortcut, as TKeyShortcuts::normalizeShortcut() does.
		 * @param {string} shortcut - a shortcut such as 'ctrl+shift+s'
		 * @returns {?string} the normalized shortcut, or null when it is not valid
		 */
		normalizeShortcut(shortcut) {
			const modifiers = new Set();
			let key = null;
			for (const token of shortcut.split('+')) {
				const lower = token.toLowerCase();
				if (Object.hasOwn(this._modifierNames, lower)) {
					modifiers.add(this._modifierNames[lower]);
					continue;
				}
				if (key !== null || (key = this.normalizeKey(token)) === null) {
					return null;
				}
			}
			if (key === null) {
				return null;
			}
			return this.MODIFIERS.filter((m) => modifiers.has(m)).concat(key).join('+');
		},

		/**
		 * Normalizes a space-separated list of shortcuts, as TKeyShortcuts::normalize() does.
		 * @param {string} value - shortcuts in the aria-keyshortcuts syntax
		 * @returns {?string} the normalized list, '' for an empty value, or null when a shortcut is not valid
		 */
		normalize(value) {
			const list = this.parse(value);
			return list ? list.join(' ') : null;
		},

		/**
		 * Parses a space-separated list of shortcuts.
		 * @param {string} value - shortcuts in the aria-keyshortcuts syntax
		 * @returns {?string[]} the normalized shortcuts, or null when one is not valid
		 */
		parse(value) {
			const list = [];
			for (const token of String(value ?? '').trim().split(/\s+/)) {
				if (token === '') {
					continue;
				}
				const shortcut = this.normalizeShortcut(token);
				if (shortcut === null) {
					return null;
				}
				if (!list.includes(shortcut)) {
					list.push(shortcut);
				}
			}
			return list;
		},

		/**
		 * Splits a normalized shortcut into its modifiers and key.
		 * @param {string} shortcut - a normalized shortcut
		 * @returns {{modifiers: string[], key: string}} the parts
		 */
		split(shortcut) {
			const parts = shortcut.split('+');
			const key = parts.pop();
			return { modifiers: parts, key };
		},

		/**
		 * @param {string} shortcut - a normalized shortcut
		 * @returns {boolean} whether the shortcut has a Control, Alt, or Meta modifier
		 */
		hasCommandModifier(shortcut) {
			return this.split(shortcut).modifiers.some((m) => m !== 'Shift');
		},

		/**
		 * @param {string} shortcut - a normalized shortcut
		 * @returns {boolean} whether the key is F1 to F24
		 */
		isFunctionKey(shortcut) {
			return /^F\d+$/.test(this.split(shortcut).key);
		},

		/**
		 * A character-key shortcut is a letter, digit, punctuation, or symbol key, alone or
		 * with Shift (WCAG 2.1.4).
		 * @param {string} shortcut - a normalized shortcut
		 * @returns {boolean} whether the shortcut is a character-key shortcut
		 */
		isCharacterKey(shortcut) {
			const key = this.split(shortcut).key;
			return !this.hasCommandModifier(shortcut) && ([...key].length === 1 || key === 'Plus');
		},

		/**
		 * Returns whether a normalized shortcut is reserved. The list matches
		 * TKeyShortcuts::getReservedShortcuts(): focus navigation and activation, editing
		 * and clipboard keys, zoom, reload, history navigation, and browser tab and window keys.
		 * @param {string} shortcut - a normalized shortcut
		 * @returns {boolean} whether the shortcut is reserved
		 */
		isReserved(shortcut) {
			if (!this._reserved) {
				const reserved = ['Tab', 'Shift+Tab', 'Control+Tab', 'Control+Shift+Tab', 'Enter', 'Space',
					'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'PageUp', 'PageDown',
					'F5', 'F6', 'F11', 'F12', 'Alt+ArrowLeft', 'Alt+ArrowRight'];
				for (const modifier of ['Control', 'Meta']) {
					for (const key of ['A', 'C', 'V', 'X', 'Z', 'Y', 'L', 'N', 'Q', 'R', 'T', 'W', 'Plus', '-', '=', '0']) {
						reserved.push(modifier + '+' + key);
					}
					for (const key of ['N', 'T', 'W', 'Z']) {
						reserved.push(this.normalizeShortcut(modifier + '+Shift+' + key));
					}
				}
				this._reserved = new Set(reserved);
			}
			return this._reserved.has(shortcut);
		},

		/**
		 * @returns {boolean} whether page-wide character-key shortcuts work; the setting
		 *   persists in localStorage and defaults to true
		 */
		getCharacterKeysEnabled() {
			if (this._characterKeys === null) {
				try {
					this._characterKeys = window.localStorage.getItem(this.STORAGE_KEY) !== 'false';
				} catch (_e) {
					this._characterKeys = true;
				}
			}
			return this._characterKeys;
		},

		/**
		 * Turns page-wide character-key shortcuts on or off and stores the choice. A page
		 * with such shortcuts offers the user a control that calls this (WCAG 2.1.4).
		 * @param {boolean} enabled - whether the shortcuts work
		 */
		setCharacterKeysEnabled(enabled) {
			this._characterKeys = !!enabled;
			try {
				window.localStorage.setItem(this.STORAGE_KEY, String(this._characterKeys));
			} catch (_e) {
				// Storage is unavailable; the setting lasts for this page.
			}
		},

		/**
		 * Registers a binding.
		 * @param {string} keys - shortcuts in the aria-keyshortcuts syntax
		 * @param {Element|string|function(KeyboardEvent, string)} action - the element to
		 *   activate, its ID, or a function called with the keyboard event and the shortcut
		 * @param {object} [options] - `scope`: an element or element ID that must contain
		 *   focus; `allowReserved`: true to bind reserved shortcuts
		 * @returns {function()} a function that removes the binding
		 * @throws {Error} when keys has no valid shortcut
		 */
		register(keys, action, options = {}) {
			const shortcuts = this.parse(keys);
			if (!shortcuts || !shortcuts.length) {
				throw new Error(`Prado.KeyShortcuts: invalid shortcuts '${keys}'`);
			}
			const binding = {
				shortcuts,
				action,
				scope: options.scope || null,
				allowReserved: !!options.allowReserved,
			};
			this._bindings.push(binding);
			return () => {
				const index = this._bindings.indexOf(binding);
				if (index !== -1) {
					this._bindings.splice(index, 1);
				}
			};
		},

		/**
		 * Returns the shortcuts declared on an element, parsed once per attribute value.
		 * @param {Element} element - an element with aria-keyshortcuts
		 * @returns {string[]} the normalized shortcuts; empty when the value is not valid
		 */
		declaredShortcuts(element) {
			const raw = element.getAttribute('aria-keyshortcuts') || '';
			const cached = this._parsed.get(element);
			if (cached && cached.raw === raw) {
				return cached.list;
			}
			const list = this.parse(raw);
			if (!list) {
				console.warn(`Prado.KeyShortcuts: invalid aria-keyshortcuts '${raw}'`, element);
			}
			this._parsed.set(element, { raw, list: list || [] });
			return list || [];
		},

		/**
		 * @param {Element|string|null} value - an element or element ID
		 * @returns {?Element} the connected element, looked up again by ID when the
		 *   element was replaced
		 */
		resolveElement(value) {
			if (!value) {
				return null;
			}
			if (typeof value === 'string') {
				return document.getElementById(value);
			}
			if (!value.isConnected && value.id) {
				return document.getElementById(value.id);
			}
			return value.isConnected ? value : null;
		},

		/**
		 * @param {Element} element - an element
		 * @returns {boolean} whether the element is connected, enabled, not inert, and visible
		 */
		isUsable(element) {
			if (!element.isConnected || element.matches(':disabled') || element.closest('[inert]')) {
				return false;
			}
			if (typeof element.checkVisibility === 'function') {
				return element.checkVisibility();
			}
			return !element.closest('[hidden]');
		},

		/**
		 * @param {?Element} element - the focused element
		 * @returns {boolean} whether typing in the element enters text
		 */
		isEditable(element) {
			if (!element || element.nodeType !== 1) {
				return false;
			}
			if (element.isContentEditable) {
				return true;
			}
			const editable = element.closest('[contenteditable]');
			if (editable && editable.getAttribute('contenteditable') !== 'false') {
				return true;
			}
			const tag = element.tagName;
			if (tag === 'TEXTAREA' || tag === 'SELECT') {
				return true;
			}
			if (tag === 'INPUT') {
				return !this._textInputExceptions.has((element.getAttribute('type') || 'text').toLowerCase());
			}
			return this._editableRoles.has(element.getAttribute('role'));
		},

		/**
		 * @param {Element} element - a bound element
		 * @returns {boolean} whether the shortcut clicks the element; otherwise it focuses it
		 */
		isActivatable(element) {
			const tag = element.tagName;
			if (tag === 'BUTTON' || tag === 'SUMMARY') {
				return true;
			}
			if ((tag === 'A' || tag === 'AREA') && element.hasAttribute('href')) {
				return true;
			}
			if (tag === 'INPUT') {
				return this._clickInputs.has((element.getAttribute('type') || 'text').toLowerCase());
			}
			return this._clickRoles.has(element.getAttribute('role'));
		},

		/**
		 * @returns {?Element} the open modal dialog, if any
		 */
		openModal() {
			try {
				return document.querySelector('dialog:modal');
			} catch (_e) {
				return null;
			}
		},

		/**
		 * Lists the shortcuts a key press can match, most specific first.
		 * @param {KeyboardEvent} event - the key press
		 * @returns {string[]} normalized shortcuts
		 */
		eventShortcuts(event) {
			if (!event.key || (event.getModifierState && event.getModifierState('AltGraph')) || this._modifierKeys.has(event.key)) {
				return [];
			}
			const modifiers = [];
			if (event.ctrlKey) {
				modifiers.push('Control');
			}
			if (event.altKey) {
				modifiers.push('Alt');
			}
			if (event.shiftKey) {
				modifiers.push('Shift');
			}
			if (event.metaKey) {
				modifiers.push('Meta');
			}
			const list = [];
			const add = (mods, key) => {
				const shortcut = mods.concat(key).join('+');
				if (!list.includes(shortcut)) {
					list.push(shortcut);
				}
			};
			let key = Object.hasOwn(this._eventKeyNames, event.key) ? this._eventKeyNames[event.key] : event.key;
			if ([...key].length === 1) {
				key = this.normalizeKey(key);
			}
			if (key !== 'Dead' && key !== 'Unidentified' && key !== 'Process') {
				add(modifiers, key);
				const symbol = key === 'Plus' || ([...key].length === 1 && key.toLowerCase() === key.toUpperCase());
				if (event.shiftKey && symbol) {
					add(modifiers.filter((m) => m !== 'Shift'), key);
				}
			}
			const code = /^(?:Key([A-Z])|Digit([0-9]))$/.exec(event.code || '');
			if (code && !/^[A-Z0-9]$/.test(key)) {
				add(modifiers, code[1] || code[2]);
			}
			return list;
		},

		/**
		 * @param {KeyboardEvent} event - the key press
		 * @returns {?Element} the focused element, inside a shadow root when there is one
		 */
		eventTarget(event) {
			const path = event.composedPath ? event.composedPath() : [];
			const target = path.length ? path[0] : event.target;
			if (target && target.nodeType === 1) {
				return target;
			}
			return document.activeElement || document.body;
		},

		/**
		 * Lists the bindings of a shortcut: registrations, latest first, then declared
		 * elements in document order.
		 * @param {string} shortcut - a normalized shortcut
		 * @returns {object[]} entries with element, handler, scope, and allowReserved
		 */
		candidates(shortcut) {
			const entries = [];
			for (let i = this._bindings.length - 1; i >= 0; i--) {
				const binding = this._bindings[i];
				if (!binding.shortcuts.includes(shortcut)) {
					continue;
				}
				const handler = typeof binding.action === 'function' ? binding.action : null;
				entries.push({
					element: handler ? null : this.resolveElement(binding.action),
					handler,
					scope: binding.scope,
					allowReserved: binding.allowReserved,
				});
			}
			for (const element of document.querySelectorAll(`[${this.MARKER}][aria-keyshortcuts]`)) {
				if (!this.declaredShortcuts(element).includes(shortcut)) {
					continue;
				}
				const reserved = element.getAttribute(this.RESERVED_MARKER);
				entries.push({
					element,
					handler: null,
					scope: element.getAttribute(this.MARKER) || null,
					allowReserved: reserved !== null && reserved !== 'false',
				});
			}
			return entries;
		},

		/**
		 * Returns the depth of the binding's scope element when the binding works for this
		 * key press, or null when it does not.
		 * @param {object} entry - a candidate entry
		 * @param {string} shortcut - the normalized shortcut
		 * @param {Element} target - the focused element
		 * @param {?Element} modal - the open modal dialog
		 * @returns {?number} -1 for page-wide, the scope depth for scoped, or null
		 */
		entryDepth(entry, shortcut, target, modal) {
			if (this.isReserved(shortcut) && !entry.allowReserved) {
				return null;
			}
			if (!entry.handler && (!entry.element || !this.isUsable(entry.element))) {
				return null;
			}
			let depth = -1;
			let scope = null;
			if (entry.scope) {
				scope = this.resolveElement(entry.scope);
				if (!scope || !scope.contains(target)) {
					return null;
				}
				depth = 0;
				for (let node = scope; node.parentNode; node = node.parentNode) {
					depth++;
				}
			} else if (this.isCharacterKey(shortcut) && !this.getCharacterKeysEnabled()) {
				return null;
			}
			const anchor = entry.element || scope;
			if (modal && (!anchor || !modal.contains(anchor))) {
				return null;
			}
			return depth;
		},

		/**
		 * Finds the binding a key press runs.
		 * @param {string} shortcut - a normalized shortcut
		 * @param {Element} target - the focused element
		 * @returns {?object} the winning entry, or null
		 */
		find(shortcut, target) {
			const modal = this.openModal();
			let best = null;
			let bestDepth = null;
			for (const entry of this.candidates(shortcut)) {
				const depth = this.entryDepth(entry, shortcut, target, modal);
				if (depth !== null && (bestDepth === null || depth > bestDepth)) {
					best = entry;
					bestDepth = depth;
				}
			}
			return best;
		},

		/**
		 * Runs a binding: calls its function, or dispatches `prado:keyshortcut` on its
		 * element and then clicks or focuses the element unless the event was cancelled.
		 * @param {object} entry - the winning entry
		 * @param {string} shortcut - the normalized shortcut
		 * @param {KeyboardEvent} event - the key press
		 */
		run(entry, shortcut, event) {
			if (entry.handler) {
				entry.handler(event, shortcut);
				return;
			}
			const element = entry.element;
			const notice = new CustomEvent('prado:keyshortcut', {
				bubbles: true,
				cancelable: true,
				detail: { shortcut, event },
			});
			if (!element.dispatchEvent(notice)) {
				return;
			}
			if (this.isActivatable(element)) {
				element.click();
			} else {
				element.focus();
			}
		},

		/**
		 * The document keydown listener.
		 * @param {KeyboardEvent} event - the key press
		 */
		onKeyDown(event) {
			if (event.defaultPrevented || event.isComposing || event.keyCode === 229) {
				return;
			}
			const shortcuts = this.eventShortcuts(event);
			if (!shortcuts.length) {
				return;
			}
			const target = this.eventTarget(event);
			const editable = this.isEditable(target);
			for (const shortcut of shortcuts) {
				if (editable && !this.hasCommandModifier(shortcut) && !this.isFunctionKey(shortcut)) {
					continue;
				}
				const entry = this.find(shortcut, target);
				if (entry) {
					event.preventDefault();
					if (!event.repeat) {
						this.run(entry, shortcut, event);
					}
					return;
				}
			}
		},
	};

	document.addEventListener('keydown', (event) => Prado.KeyShortcuts.onKeyDown(event));
}
