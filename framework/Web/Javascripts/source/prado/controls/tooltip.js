/*! PRADO TTooltip javascript file | github.com/pradosoft/prado */

/**
 * TTooltip control.
 *
 * Shows the tooltip popover next to its target while the pointer is over the
 * target or the tooltip, or the target has focus (WCAG 1.4.13). The pointer
 * shows it after ShowDelay and focus at once; it hides HideDelay after both
 * end, so the pointer crosses from the target onto the tooltip. Esc hides it
 * without closing an enclosing dialog. The target's `aria-describedby` names
 * the tooltip.
 */
Prado.WebUI.TTooltip = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.target = document.getElementById(options.TargetID);
		if (!this.element || !this.target)
			return;
		this.showDelay = options.ShowDelay ?? 300;
		this.hideDelay = options.HideDelay ?? 150;
		this.hovered = false;
		this.focused = false;
		this.timer = null;
		Prado.WebUI.TTooltip.describe(this.target, this.element.id);

		const hover = (value) => () => { this.hovered = value; this.update(this.showDelay); };
		this.observe(this.target, 'pointerenter', hover(true));
		this.observe(this.target, 'pointerleave', hover(false));
		this.observe(this.element, 'pointerenter', hover(true));
		this.observe(this.element, 'pointerleave', hover(false));
		this.observe(this.target, 'focusin', () => { this.focused = true; this.update(0); });
		this.observe(this.target, 'focusout', () => { this.focused = false; this.update(0); });
		this.observe(document, 'keydown', (event) => this.onKeyDown(event));
	},

	/**
	 * Shows the tooltip after `delay` while the target is hovered or focused, and
	 * hides it after HideDelay otherwise.
	 * @param {number} delay - the milliseconds before showing
	 */
	update(delay) {
		if (this.hovered || this.focused) {
			if (!this.isOpen())
				this.schedule(() => this.show(), delay);
			else
				this.cancel();
		} else {
			this.schedule(() => this.hide(), this.hideDelay);
		}
	},

	schedule(callback, delay) {
		this.cancel();
		this.timer = setTimeout(() => { this.timer = null; callback(); }, delay);
	},

	cancel() {
		if (this.timer !== null) {
			clearTimeout(this.timer);
			this.timer = null;
		}
	},

	/**
	 * @returns {boolean} whether the tooltip is showing
	 */
	isOpen() {
		return this.element.matches(':popover-open');
	},

	/**
	 * Shows the tooltip anchored to its target.
	 */
	show() {
		if (!this.isOpen() && this.element.isConnected)
			this.element.showPopover({ source: this.target });
	},

	hide() {
		if (this.isOpen())
			this.element.hidePopover();
	},

	/**
	 * Hides a showing tooltip on Esc. The key does not reach an enclosing dialog
	 * or popover, which stays open.
	 * @param {KeyboardEvent} event - the keydown event
	 */
	onKeyDown(event) {
		if (event.key !== 'Escape' || !this.isOpen())
			return;
		this.cancel();
		this.hide();
		event.preventDefault();
		event.stopPropagation();
	},

	onDone() {
		this.cancel();
	}
});

/**
 * Adds an ID to the `aria-describedby` of an element, once.
 * @param {Element} target - the described element
 * @param {string} id - the ID of the description
 */
Prado.WebUI.TTooltip.describe = function(target, id) {
	const ids = (target.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
	if (!ids.includes(id)) {
		ids.push(id);
		target.setAttribute('aria-describedby', ids.join(' '));
	}
};
