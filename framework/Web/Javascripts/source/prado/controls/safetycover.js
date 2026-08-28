/*! PRADO TSafetyCover javascript file | github.com/pradosoft/prado */

/**
 * TSafetyCover control.
 *
 * Keeps the panel body content behind an overlay. A click on the overlay pulses
 * the panel, then moves the overlay aside (per the server-set effect) to open the
 * content. The overlay returns after `AutoCloseDelay` milliseconds, or after the
 * pointer leaves the panel for `MouseOutTimeout` milliseconds. Re-entering the
 * panel cancels the pending close.
 *
 * DOM structure rendered by the server-side control:
 *
 *   <div id="{ID}" class="safety-cover">
 *     <div id="{ID}_slider" class="safety-cover-slider">
 *       <div id="{ID}_overlay" class="safety-cover-overlay">   <!-- guard: blocks clicks -->
 *         <div id="{ID}_face" class="safety-cover-face"> overlay template </div>
 *       </div>
 *     </div>
 *     <div id="{ID}_content" class="safety-cover-content"> body content </div>
 *   </div>
 *
 * The open state is the `safety-cover-open` CSS class on the slider element; this
 * wrapper toggles it and binds open to the `_overlay` guard on both click and
 * Enter/Space. The stylesheet does the visuals: the guard's pointer-events block
 * the mouse while closed and clear the instant the cover opens, and the `_face`
 * skin animates from the effect and direction classes the server renders on the
 * panel (`safety-cover-slide`, `-collapse`, or `-none`, with `-up`/`-down`/
 * `-left`/`-right`, and an optional `-fade`), over
 * `--safety-cover-animation-duration`.
 *
 * Because pointer-events only stop the mouse, the wrapper also marks the `_content`
 * element `inert` while closed (with an `aria-hidden`/tabindex fallback), so
 * keyboard and assistive-technology users cannot reach the guarded controls
 * behind the cover; it toggles `aria-expanded` on the guard and moves focus into
 * the content on a keyboard open, back to the guard on close.
 *
 * The pulse is the `safety-cover-pulsate` class on the panel, a keyframe
 * animation whose duration the server sets from `OpenDelay` via the
 * `--safety-cover-open-delay` custom property.
 *
 * ```javascript
 * const guard = Prado.Registry['ctl0_Content_Guarded'];
 * guard.open();    // pulse, then reveal the content
 * guard.close();   // re-guard the content (guard blocks at once, face animates back)
 * guard.isOpen()   // whether the content is reachable
 * ```
 */
Prado.WebUI.TSafetyCover = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.options = options || {};
		this.panel = this.element;
		this.slider = document.getElementById(this.ID + '_slider');
		this.overlay = document.getElementById(this.ID + '_overlay');
		this.content = document.getElementById(this.ID + '_content');
		this.opened = false;
		this.pulsing = false;
		this.openTimer = null;
		this.closeTimer = null;
		this.mouseOutTimer = null;
		this.savedTabindex = null;
		this.focusOnOpen = false;
		this.contentGuarded = false;
		this.ready = false;
		if (!this.panel || !this.slider || !this.overlay) {
			return;
		}
		this.ready = true;
		this.observe(this.overlay, 'click', this.overlayClicked.bind(this));
		this.observe(this.overlay, 'keydown', this.overlayKeydown.bind(this));
		this.observe(this.panel, 'mouseleave', this.mouseLeft.bind(this));
		this.observe(this.panel, 'mouseenter', this.mouseEntered.bind(this));
		// When KeepOpenWhileActive, interaction inside the open panel resets the
		// auto-close timer, so a complex interaction keeps the cover open.
		if (this.getKeepOpenWhileActive()) {
			const onActivity = this.onActivity.bind(this);
			for (const type of ['mousemove', 'keydown', 'pointerdown', 'input']) {
				this.observe(this.panel, type, onActivity);
			}
		}
		// Closed at load: keep the guarded content out of the tab order and the
		// accessibility tree until the cover opens, so keyboard and AT users cannot
		// reach it behind the cover (the pointer-events guard only blocks the mouse).
		this.setContentGuarded(true);
	},

	/**
	 * @return int milliseconds between the click and the overlay moving aside
	 */
	getOpenDelay() {
		return this.options.OpenDelay ?? 800;
	},

	/**
	 * @return int milliseconds before the cover auto-closes, from opening or, with
	 *   KeepOpenWhileActive, from the last interaction
	 */
	getAutoCloseDelay() {
		return this.options.AutoCloseDelay ?? 6000;
	},

	/**
	 * @return int milliseconds after the pointer leaves before the overlay returns
	 */
	getMouseOutTimeout() {
		return this.options.MouseOutTimeout ?? 1000;
	},

	/**
	 * @return bool whether interaction with the open content resets the auto-close
	 */
	getKeepOpenWhileActive() {
		return this.options.KeepOpenWhileActive === true;
	},

	/**
	 * @return bool whether the content is open and reachable
	 */
	isOpen() {
		return this.opened;
	},

	/**
	 * Handles the click on the guard. A mouse open does not move focus.
	 */
	overlayClicked(event) {
		event.preventDefault();
		this.open(false);
	},

	/**
	 * Handles Enter/Space on the guard, the keyboard equivalent of a click. A
	 * keyboard open moves focus into the revealed content.
	 */
	overlayKeydown(event) {
		if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
			event.preventDefault();
			this.open(true);
		}
	},

	/**
	 * Pulses the panel, then moves the overlay aside after `OpenDelay`
	 * milliseconds, revealing the content and making it reachable again. The
	 * overlay returns on its own after `AutoCloseDelay` milliseconds.
	 * @param bool focusContentOnOpen whether to move focus into the content once open
	 */
	open(focusContentOnOpen) {
		if (!this.ready || this.opened || this.pulsing) {
			return;
		}
		this.focusOnOpen = focusContentOnOpen === true;
		this.pulsing = true;
		this.panel.classList.add('safety-cover-pulsate');
		// Acknowledge the activation to assistive tech right away, rather than only
		// after the OpenDelay pulse; close() resets it if the open is cancelled.
		this.overlay.setAttribute('aria-expanded', 'true');
		this.openTimer = setTimeout(() => {
			this.openTimer = null;
			this.pulsing = false;
			this.panel.classList.remove('safety-cover-pulsate');
			this.slider.classList.add('safety-cover-open');
			this.opened = true;
			// Open: the guard is done revealing, so drop it from the tab order
			// rather than leaving a focusable button whose activation is a no-op.
			this.overlay.setAttribute('tabindex', '-1');
			this.setContentGuarded(false);
			if (this.focusOnOpen) {
				this.focusContent();
			}
			this.closeTimer = setTimeout(this.close.bind(this), this.getAutoCloseDelay());
		}, this.getOpenDelay());
	},

	/**
	 * Returns the overlay over the content, re-guards the content from keyboard
	 * and assistive technology, and cancels the pending timers. Focus that was
	 * inside the content returns to the guard.
	 */
	close() {
		if (!this.ready) {
			return;
		}
		this.clearTimers();
		this.pulsing = false;
		this.panel.classList.remove('safety-cover-pulsate');
		this.slider.classList.remove('safety-cover-open');
		this.opened = false;
		this.overlay.setAttribute('aria-expanded', 'false');
		// Closed: the guard is the reveal affordance again, so it returns to the
		// tab order.
		this.overlay.setAttribute('tabindex', '0');
		const focusInContent = !!this.content && this.content.contains(document.activeElement);
		this.setContentGuarded(true);
		if (focusInContent) {
			this.overlay.focus();
		}
	},

	/**
	 * Guards or reveals the content for keyboard and assistive technology. When
	 * guarded, the content leaves the tab order and the accessibility tree. Uses
	 * the `inert` attribute where supported, otherwise `aria-hidden` plus a
	 * tabindex sweep of the focusable descendants. Idempotent: repeated calls in
	 * the same state are a no-op, so the fallback never re-reads an already
	 * lowered tabindex as the value to restore.
	 * @param bool guarded whether the content is closed off
	 */
	setContentGuarded(guarded) {
		if (!this.content || guarded === this.contentGuarded) {
			return;
		}
		this.contentGuarded = guarded;
		if ('inert' in HTMLElement.prototype) {
			this.content.inert = guarded;
			return;
		}
		if (guarded) {
			this.content.setAttribute('aria-hidden', 'true');
			this.savedTabindex = [];
			const focusables = this.content.querySelectorAll('a[href], button, input, select, textarea, [tabindex]');
			for (const el of focusables) {
				this.savedTabindex.push([el, el.getAttribute('tabindex')]);
				el.setAttribute('tabindex', '-1');
			}
		} else {
			this.content.removeAttribute('aria-hidden');
			for (const [el, prev] of this.savedTabindex || []) {
				if (prev === null) {
					el.removeAttribute('tabindex');
				} else {
					el.setAttribute('tabindex', prev);
				}
			}
			this.savedTabindex = null;
		}
	},

	/**
	 * Moves focus to the first focusable element in the content, or to the content
	 * itself when it holds none.
	 */
	focusContent() {
		if (!this.content) {
			return;
		}
		const target = this.content.querySelector(
			'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
		);
		if (target) {
			target.focus();
		} else {
			// Focus the content itself when it has no focusable child. The
			// tabindex is only needed for the focus() call; remove it afterward so
			// no stray attribute is left on the author's element (focus is retained
			// once set).
			this.content.setAttribute('tabindex', '-1');
			this.content.focus();
			this.content.removeAttribute('tabindex');
		}
	},

	/**
	 * Schedules the overlay to return when the pointer leaves an open panel.
	 */
	mouseLeft() {
		if (!this.opened || this.mouseOutTimer) {
			return;
		}
		this.mouseOutTimer = setTimeout(() => {
			this.mouseOutTimer = null;
			this.close();
		}, this.getMouseOutTimeout());
	},

	/**
	 * Cancels the pending close when the pointer re-enters the panel.
	 */
	mouseEntered() {
		if (this.mouseOutTimer) {
			clearTimeout(this.mouseOutTimer);
			this.mouseOutTimer = null;
		}
	},

	/**
	 * Resets the auto-close timer on interaction while open, and cancels a pending
	 * mouse-out close, so an ongoing interaction keeps the cover open. Only in
	 * effect when `KeepOpenWhileActive` is set and the cover is open.
	 */
	onActivity() {
		if (!this.opened) {
			return;
		}
		if (this.closeTimer) {
			clearTimeout(this.closeTimer);
		}
		this.closeTimer = setTimeout(this.close.bind(this), this.getAutoCloseDelay());
		if (this.mouseOutTimer) {
			clearTimeout(this.mouseOutTimer);
			this.mouseOutTimer = null;
		}
	},

	/**
	 * Clears every pending timer.
	 */
	clearTimers() {
		for (const name of ['openTimer', 'closeTimer', 'mouseOutTimer']) {
			if (this[name]) {
				clearTimeout(this[name]);
				this[name] = null;
			}
		}
	},

	onDone() {
		this.clearTimers();
		// Restore the content on teardown so a wrapper that later re-registers on
		// the same DOM starts from an unguarded state; the fallback's tabindex save
		// then reads the real originals, not an already-lowered value.
		this.setContentGuarded(false);
	}
});
