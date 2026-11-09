/*! PRADO TDrawer javascript file | github.com/pradosoft/prado */

/**
 * TDrawer control.
 *
 * With DockAt, switches the drawer by the width: at or above it, an open non-modal
 * dialog with no `closedby`; below it, closed with `closedby`, for an invoker to
 * open modally. The server renders the drawer `open`, so a docked drawer takes no
 * focus on load; show() runs only when the width returns to docked.
 *
 * A touch swipe toward the page edge drags the drawer and closes it past a third of
 * its size or at speed; otherwise it snaps back. Touch events carry the swipe because
 * a `preventDefault()` on `touchmove` decides per gesture: content that scrolls in
 * the direction of the finger keeps the gesture, so a bottom sheet closes on a
 * downward swipe only when it is scrolled to the top.
 *
 * Where the browser lacks `closedby`, a click on the backdrop closes a modal drawer
 * with ClosedBy Any, and Esc leaves a drawer with ClosedBy None open.
 */
Prado.WebUI.TDrawer = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		if (!this.element)
			return;
		this.placement = options.Placement;
		this.closedBy = options.ClosedBy;
		this.swipe = null;
		if (options.DockAt) {
			this.media = matchMedia(`(min-width: ${options.DockAt})`);
			this.onMediaChange = () => this.syncDock();
			this.media.addEventListener('change', this.onMediaChange);
			this.syncDock();
		}
		if (options.Swipe) {
			this.observe(this.element, 'touchstart', (event) => this.onTouchStart(event));
			this.observe(this.element, 'touchmove', (event) => this.onTouchMove(event));
			this.observe(this.element, 'touchend', (event) => this.onTouchEnd(event));
			this.observe(this.element, 'touchcancel', () => this.endSwipe(false));
		}
		if (!('closedBy' in HTMLDialogElement.prototype)) {
			if (this.closedBy === 'any')
				this.observe(this.element, 'click', (event) => this.onClick(event));
			else if (this.closedBy === 'none')
				this.observe(this.element, 'cancel', (event) => event.preventDefault());
		}
	},

	/**
	 * @returns {boolean} whether the width docks the drawer
	 */
	isDocked() {
		return !!this.media && this.media.matches;
	},

	/**
	 * Applies the state of the width: docked and open, or closed for an invoker. A
	 * modal drawer closes when the width docks it, and stays modal otherwise.
	 */
	syncDock() {
		const el = this.element;
		const modal = el.matches(':modal');
		if (this.isDocked()) {
			if (modal)
				el.close();
			el.removeAttribute('closedby');
			if (!el.open)
				el.show();
		} else {
			if (this.closedBy)
				el.setAttribute('closedby', this.closedBy);
			if (el.open && !modal)
				el.close();
		}
	},

	/**
	 * @returns {number} the signed unit of the closing direction on its axis
	 */
	closingSign() {
		const rtl = getComputedStyle(this.element).direction === 'rtl';
		switch (this.placement) {
			case 'start': return rtl ? 1 : -1;
			case 'end': return rtl ? -1 : 1;
			case 'top': return -1;
			default: return 1;
		}
	},

	/**
	 * @returns {boolean} whether the drawer closes along the horizontal axis
	 */
	isHorizontal() {
		return this.placement === 'start' || this.placement === 'end';
	},

	/**
	 * Records the start of a one-finger touch on an open drawer that is not docked.
	 * @param {TouchEvent} event - the touchstart event
	 */
	onTouchStart(event) {
		if (event.touches.length !== 1 || !this.element.open || this.isDocked()) {
			this.swipe = null;
			return;
		}
		const touch = event.touches[0];
		this.swipe = { x: touch.clientX, y: touch.clientY, time: event.timeStamp, active: false, distance: 0, target: event.target };
	},

	/**
	 * Starts the swipe past the touch slop when the finger moves toward the page edge
	 * and no content scrolls that way, then drags the drawer with the finger.
	 * @param {TouchEvent} event - the touchmove event
	 */
	onTouchMove(event) {
		const swipe = this.swipe;
		if (!swipe)
			return;
		const touch = event.touches[0];
		const dx = touch.clientX - swipe.x;
		const dy = touch.clientY - swipe.y;
		const along = (this.isHorizontal() ? dx : dy) * this.closingSign();
		if (!swipe.active) {
			const across = Math.abs(this.isHorizontal() ? dy : dx);
			if (Math.max(Math.abs(along), across) < Prado.WebUI.TDrawer.SLOP)
				return;
			if (along <= across || this.canScroll(swipe.target)) {
				this.swipe = null;
				return;
			}
			swipe.active = true;
			this.element.style.transition = 'none';
		}
		if (event.cancelable)
			event.preventDefault();
		swipe.distance = Math.max(0, along);
		const offset = swipe.distance * this.closingSign();
		this.element.style.transform = this.isHorizontal() ? `translateX(${offset}px)` : `translateY(${offset}px)`;
	},

	/**
	 * Closes the drawer past a third of its size or at speed.
	 * @param {TouchEvent} event - the touchend event
	 */
	onTouchEnd(event) {
		const swipe = this.swipe;
		if (!swipe || !swipe.active) {
			this.swipe = null;
			return;
		}
		const rect = this.element.getBoundingClientRect();
		const size = this.isHorizontal() ? rect.width : rect.height;
		const elapsed = Math.max(1, event.timeStamp - swipe.time);
		this.endSwipe(swipe.distance > size / 3 || swipe.distance / elapsed > Prado.WebUI.TDrawer.SPEED);
	},

	/**
	 * Ends the swipe, requesting the close when it passed, and restores the styles
	 * so the drawer slides out or snaps back. A `cancel` handler can keep it open.
	 * @param {boolean} close - whether the swipe closes the drawer
	 */
	endSwipe(close) {
		this.swipe = null;
		const style = this.element.style;
		style.transition = '';
		style.transform = '';
		if (!close)
			return;
		if (this.element.requestClose)
			this.element.requestClose();
		else
			this.element.close();
	},

	/**
	 * @param {Element} from - the touched element
	 * @returns {boolean} whether content from the touched element up to the drawer
	 *   scrolls in the direction the finger moves
	 */
	canScroll(from) {
		const horizontal = this.isHorizontal();
		const sign = this.closingSign();
		for (let el = from; el; el = el.parentElement) {
			const style = getComputedStyle(el);
			const overflow = horizontal ? style.overflowX : style.overflowY;
			if (overflow === 'auto' || overflow === 'scroll') {
				const position = horizontal ? el.scrollLeft : el.scrollTop;
				const range = horizontal ? el.scrollWidth - el.clientWidth : el.scrollHeight - el.clientHeight;
				const min = horizontal && style.direction === 'rtl' ? -range : 0;
				if (sign > 0 ? position > min : position < min + range)
					return true;
			}
			if (el === this.element)
				break;
		}
		return false;
	},

	/**
	 * Closes a modal drawer on a click on its backdrop, outside its box.
	 * @param {MouseEvent} event - the click event of the drawer
	 */
	onClick(event) {
		if (event.target !== this.element || !this.element.matches(':modal'))
			return;
		const rect = this.element.getBoundingClientRect();
		if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)
			this.element.close();
	},

	onDone() {
		if (this.media)
			this.media.removeEventListener('change', this.onMediaChange);
	}
});

/**
 * The pixels a finger moves before the gesture is a swipe or not.
 */
Prado.WebUI.TDrawer.SLOP = 10;

/**
 * The pixels per millisecond at which a swipe closes the drawer at any distance.
 */
Prado.WebUI.TDrawer.SPEED = 0.5;
