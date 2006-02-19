Prado.WebUI.TRatingList = Class.create();	
Prado.WebUI.TRatingList.prototype = 
{
	selectedIndex : -1,

	initialize : function(options)
	{
		this.options = options;
		this.element = $(options['ID']);
		Element.addClassName(this.element,options.cssClass);
		this.radios = document.getElementsByName(options.field);
		for(var i = 0; i<this.radios.length; i++)
		{
			Event.observe(this.radios[i].parentNode, "mouseover", this.hover.bindEvent(this,i));
			Event.observe(this.radios[i].parentNode, "mouseout", this.recover.bindEvent(this,i));
			Event.observe(this.radios[i].parentNode, "click", this.click.bindEvent(this, i));
		}		
		this.caption = CAPTION();
		this.element.appendChild(this.caption);
		this.selectedIndex = options.selectedIndex;
		this.setRating(this.selectedIndex);
	},
	
	hover : function(ev,index)
	{
		for(var i = 0; i<this.radios.length; i++)
			this.radios[i].parentNode.className = (i<=index) ? "rating_hover" : "";
		this.setCaption(index);
	},
	
	recover : function(ev,index)
	{
		for(var i = 0; i<=index; i++)
			Element.removeClassName(this.radios[i].parentNode, "rating_hover");
		this.setRating(this.selectedIndex);
	},
	
	click : function(ev, index)
	{
		for(var i = 0; i<this.radios.length; i++)
			this.radios[i].checked = (i == index);
		this.selectedIndex = index;
		this.setRating(index);
		if(isFunction(this.options.onChange))
			this.options.onChange(this,index);		
	},
	
	setRating: function(index)
	{
		for(var i = 0; i<=index; i++)
			this.radios[i].parentNode.className = "rating_selected";
		this.setCaption(index);
	},
	
	setCaption : function(index)
	{
		this.caption.innerHTML = index > -1 ? 
			this.radios[index].value : this.options.caption;	
	}
}