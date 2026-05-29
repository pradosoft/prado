/*! PRADO TSlider javascript file | github.com/pradosoft/prado */

/**
 * TSlider client class.
 * This clas is mainly based on Scriptaculous Slider control (http://script.aculo.us)
 */

/**
 * Compute viewport-relative offset of an element (jQuery.fn.offset equivalent).
 * @since 4.4.0
 */
const _sliderOffset = (el) => {
	const rect = el.getBoundingClientRect();
	return { top: rect.top + window.pageYOffset, left: rect.left + window.pageXOffset };
};

/**
 * Visibility check (jQuery's :visible filter equivalent).
 * @since 4.4.0
 */
const _sliderVisible = (el) =>
	!!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length));

Prado.WebUI.TSlider = Prado.Class(Prado.WebUI.PostBackControl,
{
	onInit(options) {
		const slider = this;
		this.options=options || {};
		this.track = document.getElementById(`${options.ID}_track`);
		this.handle = document.getElementById(`${options.ID}_handle`);
		this.progress = document.getElementById(`${options.ID}_progress`);
		this.axis  = this.options.axis || 'horizontal';
		this.range = this.options.range || [0, 1];
		this.value = 0;
		this.maximum   = this.options.maximum || this.range[1];
		this.minimum   = this.options.minimum || this.range[0];
		this.hiddenField = document.getElementById(`${this.options.ID}_1`);
		this.trackInitialized=false;

		this.initializeTrack();

		this.active   = false;
		this.dragging = false;
		this.disabled = false;

		if(this.options.disabled) this.setDisabled();

		// Allowed values array
		this.allowedValues = this.options.values ? this.options.values.sort() : false;
		if(this.allowedValues) {
			this.minimum = Math.min.apply( Math, this.allowedValues );
			this.maximum = Math.max.apply( Math, this.allowedValues );
		}

		this.eventMouseDown = this.startDrag.bind(this);
		this.eventMouseUp   = this.endDrag.bind(this);
		this.eventMouseMove = this.update.bind(this);

		// Initialize handle
		this.setValue(parseFloat(slider.options.sliderValue));
		this.observe (this.handle, "mousedown", this.eventMouseDown);

		this.observe (this.track, "mousedown", this.eventMouseDown);
		if (this.progress) this.observe (this.progress, "mousedown", this.eventMouseDown);

		this.observe (document, "mouseup", this.eventMouseUp);
		this.observe (document, "mousemove", this.eventMouseMove);

		this.initialized=true;

		if(this.options['AutoPostBack']==true)
			this.observe(this.hiddenField, "change", this.doPostback.bind(this, options));
	},

	initializeTrack() {
		if(this.trackInitialized || !_sliderVisible(this.track))
			return;

		// Will be used to align the handle onto the track, if necessary
		this.alignX = parseInt(this.options.alignX || - this.track.offsetLeft);
		this.alignY = parseInt(this.options.alignY || - this.track.offsetTop);

		this.trackLength = this.maximumOffset() - this.minimumOffset();
		this.handleLength = this.isVertical() ?
			(this.handle.offsetHeight != 0 ?
				this.handle.offsetHeight : this.handles.style.height.replace(/px$/,"")) :
				(this.handle.offsetWidth != 0 ? this.handle.offsetWidth :
					this.handle.style.width.replace(/px$/,""));
		this.trackInitialized=true;
	},

	doPostback(options, event) {
		new Prado.PostBack(options, event);
	},

	setDisabled() {
		this.disabled = true;
	},
	setEnabled() {
		this.disabled = false;
	},
	getNearestValue(value) {
		if(this.allowedValues){
			const max = Math.max.apply( Math, this.allowedValues );
			const min = Math.min.apply( Math, this.allowedValues );
			if(value >= max) return(max);
			if(value <= min) return(min);

			let offset = Math.abs(this.allowedValues[0] - value);
			let newValue = this.allowedValues[0];
			for (const v of this.allowedValues) {
				const currentOffset = Math.abs(v - value);
				if(currentOffset <= offset){
					newValue = v;
					offset = currentOffset;
				}
			}
			return newValue;
		}
		if(value > this.range[1]) return this.range[1];
		if(value < this.range[0]) return this.range[0];
		return value;
	},

	setValue(sliderValue) {
		if(!this.active) {
			this.updateStyles();
		}
		this.value = this.getNearestValue(sliderValue);
		const pixelValue= this.translateToPx(this.value);
		this.handle.style[this.isVertical() ? 'top' : 'left'] =	pixelValue;
		if (this.progress)
			this.progress.style[this.isVertical() ? 'height' : 'width'] = pixelValue;

		//this.drawSpans();
		if(!this.dragging || !this.event) this.updateFinished();
	},

	setValueBy(delta) {
    	this.setValue(this.value + delta);
	},

	translateToPx(value) {
		return `${Math.round(
    ((this.trackLength-this.handleLength)/(this.range[1]-this.range[0])) * (value - this.range[0]))}px`;
	},

	translateToValue(offset) {
		return ((offset/(this.trackLength-this.handleLength) * (this.range[1]-this.range[0])) + this.range[0]);
	},

	minimumOffset() {
		return(this.isVertical() ? this.alignY : this.alignX);
  	},

	maximumOffset() {
		return(this.isVertical() ?
			(this.track.offsetHeight != 0 ? this.track.offsetHeight :
				this.track.style.height.replace(/px$/,"")) - this.alignY :
				(this.track.offsetWidth != 0 ? this.track.offsetWidth :
				this.track.style.width.replace(/px$/,"")) - this.alignX);
	},

	isVertical() {
		return (this.axis == 'vertical');
	},

	updateStyles() {
		if (this.active)
			this.handle.classList.add('selected');
		else
			this.handle.classList.remove('selected');
	},

	startDrag(event) {
		if (event.which === 1) {
			this.initializeTrack();
			// left click
			if(!this.disabled){
				this.active = true;
				const handle = event.target;
				const pointer  = [event.pageX, event.pageY];
				const track = handle;
				let offsets;
				if(track==this.track) {
					offsets = _sliderOffset(this.track);
					this.event = event;
					this.setValue(this.translateToValue(
						(this.isVertical() ? pointer[1]-offsets['top'] : pointer[0]-offsets['left'])-(this.handleLength/2)
					));
					offsets = _sliderOffset(this.handle);
					this.offsetX = (pointer[0] - offsets['left']);
					this.offsetY = (pointer[1] - offsets['top']);
				} else {
					this.updateStyles();
					offsets = _sliderOffset(this.handle);
					this.offsetX = (pointer[0] - offsets['left']);
					this.offsetY = (pointer[1] - offsets['top']);
				}
			}
			event.stopPropagation();
		}
	},

	update(event) {
		if(this.active) {
			if(!this.dragging) this.dragging = true;
			this.draw(event);
			event.stopPropagation();
		}
	},

	draw(event) {
		const pointer = [event.pageX, event.pageY];
		const offsets = _sliderOffset(this.track);
		pointer[0] -= this.offsetX + offsets['left'];
		pointer[1] -= this.offsetY + offsets['top'];
		this.event = event;
		this.setValue(this.translateToValue( this.isVertical() ? pointer[1] : pointer[0] ));
		if(this.initialized && this.options.onSlide)
			this.options.onSlide(this.value, this);
	},

	endDrag(event) {
		if(this.active && this.dragging) {
			this.finishDrag(event, true);
			event.stopPropagation();
		}
		this.active = false;
		this.dragging = false;
	},

	finishDrag(_event, _success) {
		this.active = false;
		this.dragging = false;
		this.updateFinished();
	},

	updateFinished() {
		this.hiddenField.value=this.value;
		this.updateStyles();
		if(this.initialized && this.options.onChange)
			this.options.onChange(this.value, this);
		this.event = null;
		if (this.options['AutoPostBack']==true)
		{
			this.hiddenField.dispatchEvent(new Event('change', { bubbles: true }));
		}
	}

});
