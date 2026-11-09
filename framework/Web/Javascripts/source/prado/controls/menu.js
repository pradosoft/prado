/*! PRADO TDropdownMenu and TContextMenu javascript file | github.com/pradosoft/prado */

/**
 * TDropdownMenu control.
 *
 * Gives a popover the ARIA `menu` role and the keyboard of the WAI-ARIA menu
 * button pattern. The links and buttons in the popover are the items: each takes
 * `role="menuitem"` and `tabindex="-1"`, and lists between the menu and the items
 * take `role="none"`. Opening the menu focuses its first item, or its last after
 * Up Arrow on a menu button; arrow keys open it and focus the item at once,
 * before the asynchronous `toggle` event. Arrow keys, Home, End, and a character move the
 * focus; Tab and activating an item close the menu. The browser closes it on Esc
 * and a click outside, and returns the focus to the menu button.
 *
 * The menu buttons are the invokers whose `commandfor` names the popover; each
 * takes `aria-haspopup="menu"`.
 */
Prado.WebUI.TDropdownMenu = Prado.Class(Prado.WebUI.Control,
{
	onInit() {
		if (!this.element)
			return;
		this.element.setAttribute('role', 'menu');
		this.refreshItems();
		for (const invoker of this.getInvokers()) {
			invoker.setAttribute('aria-haspopup', 'menu');
			this.observe(invoker, 'keydown', (event) => this.onInvokerKeyDown(event, invoker));
		}
		this.observe(this.element, 'toggle', (event) => this.onToggle(event));
		this.observe(this.element, 'keydown', (event) => this.onKeyDown(event));
		this.observe(this.element, 'click', (event) => this.onClick(event));
	},

	/**
	 * @returns {Element[]} the invokers whose `commandfor` names the menu
	 */
	getInvokers() {
		return Array.from(document.querySelectorAll('[commandfor]'))
			.filter((el) => el.getAttribute('commandfor') === this.element.id);
	},

	/**
	 * Finds the enabled items and gives them their roles and `tabindex`.
	 */
	refreshItems() {
		this.items = Array.from(this.element.querySelectorAll(Prado.WebUI.TDropdownMenu.ITEMS))
			.filter((el) => !el.disabled && !el.hidden);
		for (const item of this.items) {
			if (!/^menuitem/.test(item.getAttribute('role') || ''))
				item.setAttribute('role', 'menuitem');
			item.setAttribute('tabindex', '-1');
			for (let el = item.parentElement; el && el !== this.element; el = el.parentElement) {
				if (/^(UL|OL|LI)$/.test(el.tagName))
					el.setAttribute('role', 'none');
			}
		}
	},

	/**
	 * @returns {boolean} whether the menu is showing
	 */
	isOpen() {
		return this.element.matches(':popover-open');
	},

	/**
	 * Opens the menu from a menu button and focuses an item.
	 * @param {Element} source - the menu button, the anchor of the menu
	 * @param {string} which - 'first' or 'last', the item to focus
	 */
	open(source, which) {
		if (!this.isOpen())
			this.element.showPopover({ source });
		this.refreshItems();
		this.focusItem(which);
	},

	close() {
		if (this.isOpen())
			this.element.hidePopover();
	},

	/**
	 * Focuses an item.
	 * @param {string|number} which - 'first', 'last', or an index, which wraps
	 */
	focusItem(which) {
		const count = this.items.length;
		if (count === 0)
			return;
		const index = which === 'first' ? 0 : which === 'last' ? count - 1 : (which + count) % count;
		this.items[index].focus();
	},

	/**
	 * Focuses the first item of a menu that opened without the focus in it, such
	 * as from a click or Enter on a menu button.
	 * @param {ToggleEvent} event - the toggle event of the popover
	 */
	onToggle(event) {
		if (event.newState !== 'open' || this.element.contains(document.activeElement))
			return;
		this.refreshItems();
		this.focusItem('first');
	},

	/**
	 * Opens the menu on its first item (Down Arrow) or its last (Up Arrow).
	 * @param {KeyboardEvent} event - the keydown event of a menu button
	 * @param {Element} invoker - the menu button
	 */
	onInvokerKeyDown(event, invoker) {
		if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp')
			return;
		event.preventDefault();
		this.open(invoker, event.key === 'ArrowDown' ? 'first' : 'last');
	},

	/**
	 * Moves the focus between the items; Tab closes the menu.
	 * @param {KeyboardEvent} event - the keydown event in the menu
	 */
	onKeyDown(event) {
		const index = this.items.indexOf(document.activeElement);
		let target = null;
		switch (event.key) {
			case 'ArrowDown': target = index + 1; break;
			case 'ArrowUp': target = index < 0 ? 'last' : index - 1; break;
			case 'Home': target = 'first'; break;
			case 'End': target = 'last'; break;
			case 'Tab': this.close(); return;
			default:
				if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey)
					target = this.findItem(event.key, index);
		}
		if (target === null)
			return;
		event.preventDefault();
		this.focusItem(target);
	},

	/**
	 * @param {string} character - the typed character
	 * @param {number} index - the index of the focused item
	 * @returns {?number} the index of the next item whose text starts with the
	 *   character, wrapping, or null
	 */
	findItem(character, index) {
		const lower = character.toLowerCase();
		const count = this.items.length;
		for (let step = 1; step <= count; step++) {
			const candidate = (index + step + count) % count;
			const text = (this.items[candidate].textContent || this.items[candidate].value || '').trim().toLowerCase();
			if (text.startsWith(lower))
				return candidate;
		}
		return null;
	},

	/**
	 * Closes the menu when an item is activated.
	 * @param {MouseEvent} event - the click event in the menu
	 */
	onClick(event) {
		const item = event.target.closest(Prado.WebUI.TDropdownMenu.ITEMS);
		if (item && this.items.includes(item))
			this.close();
	}
});

/**
 * The selector of the menu items: links, buttons, and elements with a menu item role.
 */
Prado.WebUI.TDropdownMenu.ITEMS = 'a[href], button, input[type="submit"], input[type="button"], [role="menuitem"], [role="menuitemcheckbox"], [role="menuitemradio"]';

/**
 * TContextMenu control.
 *
 * A TDropdownMenu that opens on the `contextmenu` event of its target, in place of
 * the browser context menu: at the pointer for a right-click or a long press, and
 * below the start of the target for Shift+F10 or the ContextMenu key, which some
 * browsers do not turn into a `contextmenu` event. The menu stays in the viewport.
 *
 * Where `contextmenu` fires while the button or finger is down, the menu opens
 * after the pointerup; opened sooner, the pointerup outside it would light
 * dismiss it.
 */
Prado.WebUI.TContextMenu = Prado.Class(Prado.WebUI.TDropdownMenu,
{
	onInit($super, options) {
		$super(options);
		this.target = document.getElementById(options.TargetID);
		if (!this.element || !this.target)
			return;
		this.pointerDown = false;
		this.pending = null;
		this.observe(this.target, 'pointerdown', (event) => {
			this.pointerDown = event.button === 2 || event.pointerType === 'touch';
		});
		this.observe(document, 'pointerup', () => this.onPointerUp());
		this.observe(this.target, 'contextmenu', (event) => this.onContextMenu(event));
		this.observe(this.target, 'keydown', (event) => this.onTargetKeyDown(event));
	},

	/**
	 * Opens the menu at the pointer, or at the target for a keyboard request.
	 * @param {MouseEvent} event - the contextmenu event of the target
	 */
	onContextMenu(event) {
		event.preventDefault();
		if (event.button === -1 || (event.clientX === 0 && event.clientY === 0))
			this.openAtTarget();
		else if (this.pointerDown)
			this.pending = { x: event.clientX, y: event.clientY };
		else
			this.openAt(event.clientX, event.clientY);
	},

	/**
	 * Opens a menu requested while the pointer was down, after the pointerup and
	 * its light dismiss.
	 */
	onPointerUp() {
		this.pointerDown = false;
		const point = this.pending;
		this.pending = null;
		if (point)
			setTimeout(() => this.openAt(point.x, point.y), 0);
	},

	/**
	 * Opens the menu at the target on Shift+F10 or the ContextMenu key.
	 * @param {KeyboardEvent} event - the keydown event of the target
	 */
	onTargetKeyDown(event) {
		if (!(event.key === 'ContextMenu' || (event.key === 'F10' && event.shiftKey)))
			return;
		event.preventDefault();
		this.openAtTarget();
	},

	openAtTarget() {
		const rect = this.target.getBoundingClientRect();
		this.openAt(rect.left, rect.bottom);
	},

	/**
	 * Opens the menu with its top-left corner at a viewport point, moved back
	 * into the viewport when it does not fit, and focuses its first item.
	 * @param {number} x - the viewport x coordinate
	 * @param {number} y - the viewport y coordinate
	 */
	openAt(x, y) {
		const style = this.element.style;
		style.inset = 'auto';
		style.margin = '0';
		style.left = x + 'px';
		style.top = y + 'px';
		if (!this.isOpen())
			this.element.showPopover({ source: this.target });
		const rect = this.element.getBoundingClientRect();
		style.left = Math.max(0, Math.min(x, window.innerWidth - rect.width)) + 'px';
		style.top = Math.max(0, Math.min(y, window.innerHeight - rect.height)) + 'px';
		this.refreshItems();
		this.focusItem('first');
	}
});
