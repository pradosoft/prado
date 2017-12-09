/*! PRADO TSlider javascript file | github.com/pradosoft/prado */

/**
 * TSlider client class.
 * This clas is mainly based on Scriptaculous Slider control (http://script.aculo.us)
 */

Prado.WebUI.TSlider = jQuery.klass(Prado.WebUI.PostBackControl,
{
	onInit : function (options)
	{
		var slider = this;
		this.options=options || {};
		this.track = jQuery('#'+options.ID+'_track').get(0);
		this.handle =jQuery('#'+options.ID+'_handle').get(0);
		this.progress = jQuery('#'+options.ID+'_progress').get(0);
		this.axis  = this.options.axis || 'horizontal';
		this.range = this.options.range || [0, 1];
		this.value = 0;
		this.maximum   = this.options.maximum || this.range[1];
		this.minimum   = this.options.minimum || this.range[0];
		this.hiddenField=jQuery('#'+this.options.ID+'_1').get(0);
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
			this.observe(this.hiddenField, "change", jQuery.proxy(this.doPostback,this,options));
	},

	initializeTrack : function()
	{
		if(this.trackInitialized || !$(this.track).is(":visible"))
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

	doPostback : function(options, event)
	{
		new Prado.PostBack(options, event);
	},

	setDisabled: function(){
		this.disabled = true;
	},
	setEnabled: function(){
		this.disabled = false;
	},
	getNearestValue: function(value){
		if(this.allowedValues){
			var max = Math.max.apply( Math, this.allowedValues );
			var min = Math.min.apply( Math, this.allowedValues );
			if(value >= max) return(max);
			if(value <= min) return(min);

			var offset = Math.abs(this.allowedValues[0] - value);
			var newValue = this.allowedValues[0];
			jQuery.each(this.allowedValues, function(idx, v) {
				var currentOffset = Math.abs(v - value);
				if(currentOffset <= offset){
					newValue = v;
					offset = currentOffset;
				}
			});
			return newValue;
		}
		if(value > this.range[1]) return this.range[1];
		if(value < this.range[0]) return this.range[0];
		return value;
	},

	setValue: function(sliderValue){
		if(!this.active) {
			this.updateStyles();
		}
		this.value = this.getNearestValue(sliderValue);
		var pixelValue= this.translateToPx(this.value);
		this.handle.style[this.isVertical() ? 'top' : 'left'] =	pixelValue;
		if (this.progress)
			this.progress.style[this.isVertical() ? 'height' : 'width'] = pixelValue;

		//this.drawSpans();
		if(!this.dragging || !this.event) this.updateFinished();
	},

	setValueBy: function(delta) {
    	this.setValue(this.value + delta);
	},

	translateToPx: function(value) {
		return Math.round(
      		((this.trackLength-this.handleLength)/(this.range[1]-this.range[0])) * (value - this.range[0])) + "px";
	},

	translateToValue: function(offset) {
		return ((offset/(this.trackLength-this.handleLength) * (this.range[1]-this.range[0])) + this.range[0]);
	},

	minimumOffset: function(){
		return(this.isVertical() ? this.alignY : this.alignX);
  	},

	maximumOffset: function(){
		return(this.isVertical() ?
			(this.track.offsetHeight != 0 ? this.track.offsetHeight :
				this.track.style.height.replace(/px$/,"")) - this.alignY :
				(this.track.offsetWidth != 0 ? this.track.offsetWidth :
				this.track.style.width.replace(/px$/,"")) - this.alignX);
	},

	isVertical:  function(){
		return (this.axis == 'vertical');
	},

	updateStyles: function() {
		if (this.active)
			jQuery(this.handle).addClass('selected');
		else
			jQuery(this.handle).removeClass('selected');
	},

	startDrag: function(event) {
		if (event.which === 1) {
			this.initializeTrack();
			// left click
			if(!this.disabled){
				this.active = true;
				var handle = event.target;
				var pointer  = [event.pageX, event.pageY];
				var track = handle;
				if(track==this.track) {
					var offsets  = jQuery(this.track).offset();
					this.event = event;
					this.setValue(this.translateToValue(
						(this.isVertical() ? pointer[1]-offsets['top'] : pointer[0]-offsets['left'])-(this.handleLength/2)
					));
					var offsets  = jQuery(this.handle).offset();
					this.offsetX = (pointer[0] - offsets['left']);
					this.offsetY = (pointer[1] - offsets['top']);
				} else {
					this.updateStyles();
					var offsets  = jQuery(this.handle).offset();
					this.offsetX = (pointer[0] - offsets['left']);
					this.offsetY = (pointer[1] - offsets['top']);
				}
			}
			event.stopPropagation();
		}
	},

	update: function(event) {
		if(this.active) {
			if(!this.dragging) this.dragging = true;
			this.draw(event);
			event.stopPropagation();
		}
	},

	draw: function(event) {
		var pointer = [event.pageX, event.pageY];
		var offsets = jQuery(this.track).offset();
		pointer[0] -= this.offsetX + offsets['left'];
		pointer[1] -= this.offsetY + offsets['top'];
		this.event = event;
		this.setValue(this.translateToValue( this.isVertical() ? pointer[1] : pointer[0] ));
		if(this.initialized && this.options.onSlide)
			this.options.onSlide(this.value, this);
	},

	endDrag: function(event) {
		if(this.active && this.dragging) {
			this.finishDrag(event, true);
			event.stopPropagation();
		}
		this.active = false;
		this.dragging = false;
	},

	finishDrag: function(event, success) {
		this.active = false;
		this.dragging = false;
		this.updateFinished();
	},

	updateFinished: function() {
		this.hiddenField.value=this.value;
		this.updateStyles();
		if(this.initialized && this.options.onChange)
			this.options.onChange(this.value, this);
		this.event = null;
		if (this.options['AutoPostBack']==true)
		{
			jQuery(this.hiddenField).trigger("change");
		}
	}

});