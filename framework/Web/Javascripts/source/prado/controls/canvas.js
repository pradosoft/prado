/*! PRADO TCanvas javascript file | github.com/pradosoft/prado */

/**
 * TCanvas client class.
 *
 * Calls the ClientSide.OnDraw handler of a TCanvas with its drawing context. The handler
 * runs when the control is created, which is on page load and again when a callback
 * renders the canvas, and when the display size changes. With MatchDisplaySize, the
 * bitmap is the display size times the device pixel ratio and a '2d' context is scaled,
 * so the handler draws in CSS pixels.
 */
Prado.WebUI.TCanvas = Prado.Class(Prado.WebUI.Control,
{
	onInit: function(options)
	{
		this.options = options || {};
		this.context = null;
		this.resizeObserver = null;
		this.lastSize = null;
		if (!this.element) {
			return;
		}
		if (this.options.MatchDisplaySize && typeof ResizeObserver !== 'undefined') {
			var self = this;
			this.resizeObserver = new ResizeObserver(function() { self.draw(); });
			this.resizeObserver.observe(this.element);
		}
		this.draw();
	},

	onDone: function()
	{
		if (this.resizeObserver) {
			this.resizeObserver.disconnect();
			this.resizeObserver = null;
		}
	},

	/**
	 * Returns the drawing size and pixel ratio. With MatchDisplaySize and a displayed
	 * element, the size is the CSS size; otherwise it is the bitmap size.
	 */
	measure: function()
	{
		var el = this.element;
		if (this.options.MatchDisplaySize) {
			var rect = el.getBoundingClientRect();
			if (rect.width > 0 && rect.height > 0) {
				return { width: rect.width, height: rect.height, pixelRatio: window.devicePixelRatio || 1 };
			}
		}
		return { width: el.width, height: el.height, pixelRatio: 1 };
	},

	/**
	 * Sizes the bitmap to the drawing size times the pixel ratio. A display size that
	 * changes with the bitmap has no CSS size, so it is fixed at its measured size to keep
	 * the resize observer from growing it.
	 */
	resizeBitmap: function(size)
	{
		var el = this.element;
		var width = Math.round(size.width * size.pixelRatio);
		var height = Math.round(size.height * size.pixelRatio);
		if (el.width === width && el.height === height) {
			return;
		}
		el.width = width;
		el.height = height;
		var rect = el.getBoundingClientRect();
		if (rect.width !== size.width) {
			el.style.width = size.width + 'px';
		}
		if (rect.height !== size.height) {
			el.style.height = size.height + 'px';
		}
	},

	/**
	 * Creates the drawing context once; a canvas keeps the type of its first context.
	 */
	getContext: function()
	{
		if (this.context === null) {
			this.context = this.element.getContext(this.options.ContextType || '2d');
		}
		return this.context;
	},

	/**
	 * Calls OnDraw when the drawing size or pixel ratio differs from the last draw.
	 */
	draw: function()
	{
		if (!this.element || typeof this.options.onDraw !== 'function') {
			return;
		}
		var size = this.measure();
		if (this.lastSize && this.lastSize.width === size.width && this.lastSize.height === size.height
			&& this.lastSize.pixelRatio === size.pixelRatio) {
			return;
		}
		this.lastSize = size;
		if (this.options.MatchDisplaySize) {
			this.resizeBitmap(size);
		}
		var context = this.getContext();
		if (context && typeof context.setTransform === 'function') {
			context.setTransform(size.pixelRatio, 0, 0, size.pixelRatio, 0, 0);
		}
		var reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
		this.options.onDraw(this, {
			canvas: this.element,
			context: context,
			width: size.width,
			height: size.height,
			pixelRatio: size.pixelRatio,
			reducedMotion: reducedMotion
		});
	}
});
