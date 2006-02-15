Prado.WebUI.TRatingList = Class.create();	
Prado.WebUI.TRatingList.prototype = 
{
	selectedIndex : -1,

	initialize : function(options)
	{
		this.options = options;
		this.element = $(options['ID']);
		Element.addClassName(this.element,options.cssClass);
		var width = options.total * options.dx;
		this.element.style.width = width+"px";
		Event.observe(this.element, 'mouseover', this.hover.bindEvent(this));
		Event.observe(this.element, 'mouseout', this.recover.bindEvent(this));
		Event.observe(this.element, 'click', this.click.bindEvent(this));
		this._onMouseMoveEvent = this.mousemoved.bindEvent(this);
		this.selectedIndex = options.pos;
		this.radios = document.getElementsByName(options.field);
		this.caption = CAPTION();
		this.element.appendChild(this.caption);
		this.showPosition(this.selectedIndex,false);
	},
	
	hover : function()
	{
		Event.observe(this.element, "mousemove", this._onMouseMoveEvent);	
	},
	
	recover : function()
	{
		Event.stopObserving(this.element, "mousemove", this._onMouseMoveEvent);
		this.showPosition(this.selectedIndex,false);
	},
	
	mousemoved : function(e)
	{
		this.updatePosition(e,true);
	},
	
	updatePosition : function(e, hovering)
	{
		var obj = Event.element(e);
		var elementPos = Position.cumulativeOffset(obj);
		var clientX = Event.pointerX(e) - elementPos[0];
		var pos = parseInt(clientX / this.options.dx);
		if(!hovering || this.options.pos != pos)
			this.showPosition(pos, hovering)
	},
	
	click : function(ev)
	{
		this.updatePosition(ev,false);
		this.selectedIndex = this.options.pos;
		for(var i = 0; i < this.radios.length; i++)
			this.radios[i].checked = (i == this.selectedIndex);
		if(isFunction(this.options.onChange))
			this.options.onChange(this, this.selectedIndex);
	},
	
	showPosition : function(pos, hovering)
	{
		if(pos >=  this.options.total) return;
		var dy = this.options.dy * (pos+1) + this.options.iy;
		var dx = hovering ? this.options.hx + this.options.ix : this.options.ix;
		this.element.style.backgroundPosition = "-"+dx+"px -"+dy+"px";
		this.options.pos = pos;
		this.caption.innerHTML = pos >= 0 ? 
				this.radios[this.options.pos].value : this.options.caption;
	}
}