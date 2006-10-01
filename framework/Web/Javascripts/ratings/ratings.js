Prado.WebUI.TRatingList = Base.extend(
{
	selectedIndex : -1,
	enabled : true,

	constructor : function(options)
	{
		var cap = $(options.CaptionID);
		this.options = Object.extend(
		{
			caption : cap ? cap.innerHTML : ''
		}, options || {});

		Prado.WebUI.TRatingList.register(this);
		this._init();
		this.selectedIndex = options.SelectedIndex;
		this.setRating(this.selectedIndex);
	},

	_init: function(options)
	{
		Element.addClassName($(this.options.ListID),this.options.Style);
		var radios = document.getElementsByName(this.options.ListName);
		this.radios = new Array();
		var index=0;
		for(var i = 0; i<radios.length; i++)
		{
			var node = radios[i].parentNode;
			if(node.tagName.toLowerCase()=='td')
			{
				this.radios.push(radios[i]);
				Event.observe(node, "mouseover", this.hover.bindEvent(this,index));
				Event.observe(node, "mouseout", this.recover.bindEvent(this,index));
				Event.observe(node, "click", this.click.bindEvent(this, index));
				index++;
				Element.addClassName(node,"rating");
			}
		}
	},

	hover : function(ev,index)
	{
		if(this.enabled==false) return;
		for(var i = 0; i<this.radios.length; i++)
		{
			var action = i <= index ? 'addClassName' : 'removeClassName'
			Element[action](this.radios[i].parentNode,"rating_hover");
		}
		this.setCaption(index);
	},

	recover : function(ev,index)
	{
		if(this.enabled==false) return;
		for(var i = 0; i<=index; i++)
			Element.removeClassName(this.radios[i].parentNode, "rating_hover");
		this.setRating(this.selectedIndex);
	},

	click : function(ev, index)
	{
		if(this.enabled==false) return;
		for(var i = 0; i<this.radios.length; i++)
			this.radios[i].checked = (i == index);
		this.selectedIndex = index;
		this.setRating(index);
		var requestOptions = Object.extend(
		{
			ID : this.options.ListID+"_c"+index,
			EventTarget : this.options.ListName+"$c"+index
		},this.options);
		var request = new Prado.CallbackRequest(requestOptions.EventTarget, requestOptions);
		if(request.dispatch()==false)
			Event.stop(ev);
	},

	setRating: function(index)
	{
		for(var i = 0; i<this.radios.length; i++)
		{
			var action = i <= index ? 'addClassName' : 'removeClassName'
			Element[action](this.radios[i].parentNode, "rating_selected");
		}
		this.setCaption(index);
	},

	setCaption : function(index)
	{
		var value = index > -1 ? this.radios[index].value : this.options.caption;
		var caption = $(this.options.CaptionID);
		if(caption) caption.innerHTML = value;
		$(this.options.ListName).title = value;
	},

	setEnabled : function(value)
	{
		this.enabled = value;
		for(var i = 0; i<this.radios.length; i++)
		{
			var action = value ? 'removeClassName' : 'addClassName'
			Element[action](this.radios[i].parentNode, "rating_disabled");
			Element.removeClassName(this.radios[i].parentNode, "rating_hover");
		}
	}
},
{
ratings : {},
register : function(rating)
{
	Prado.WebUI.TRatingList.ratings[rating.options.ListID] = rating;
},

setEnabled : function(id,value)
{
	Prado.WebUI.TRatingList.ratings[id].setEnabled(value);
},

setRating : function(id,value)
{
	Prado.WebUI.TRatingList.ratings[id].setRating(value);
	Prado.WebUI.TRatingList.ratings[id].selectedIndex = value;
}
});