Prado.WebUI.TRatingList = Base.extend(
{
	selectedIndex : -1,
	rating: -1,
	enabled : true,
	readOnly : false,

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
		this.rating = options.Rating;
		if(options.Rating <= 0 && options.SelectedIndex >= 0)
			this.rating = options.SelectedIndex+1;
		this.showRating(this.rating);
	},

	_init: function(options)
	{
		Element.addClassName($(this.options.ListID),this.options.Style);
		this.radios = new Array();
		var index=0;
		for(var i = 0; i<this.options.ItemCount; i++)
		{
			var radio = $(this.options.ListID+'_c'+i);
			var td = radio.parentNode;
			if(radio && td.tagName.toLowerCase()=='td')
			{
				this.radios.push(radio);
				Event.observe(td, "mouseover", this.hover.bindEvent(this,index));
				Event.observe(td, "mouseout", this.recover.bindEvent(this,index));
				Event.observe(td, "click", this.click.bindEvent(this, index));
				index++;
				Element.addClassName(td,"rating");
			}
		}
	},

	hover : function(ev,index)
	{
		if(this.enabled==false) return;
		for(var i = 0; i<this.radios.length; i++)
		{
			var node = this.radios[i].parentNode;
			var action = i <= index ? 'addClassName' : 'removeClassName'
			Element[action](node,"rating_hover");
			Element.removeClassName(node,"rating_selected");
			Element.removeClassName(node,"rating_half");
		}
		this.showCaption(this.getIndexCaption(index));
	},

	recover : function(ev,index)
	{
		if(this.enabled==false) return;
		this.showRating(this.rating);
		this.showCaption(this.options.caption);
	},

	click : function(ev, index)
	{
		if(this.enabled==false) return;
		for(var i = 0; i<this.radios.length; i++)
			this.radios[i].checked = (i == index);

		this.selectedIndex = index;
		this.setRating(index+1);

		this.dispatchRequest(ev);
	},

	dispatchRequest : function(ev)
	{
		var requestOptions = Object.extend(
		{
			ID : this.options.ListID+"_c"+this.selectedIndex,
			EventTarget : this.options.ListName+"$c"+this.selectedIndex
		},this.options);
		var request = new Prado.CallbackRequest(requestOptions.EventTarget, requestOptions);
		if(request.dispatch()==false)
			Event.stop(ev);
	},

	setRating : function(value)
	{
		this.rating = value;
		var base = Math.floor(value-1);
		var remainder = value - base-1;
		var halfMax = this.options.HalfRating["1"];
		var index = remainder > halfMax ? base+1 : base;
		for(var i = 0; i<this.radios.length; i++)
			this.radios[i].checked = (i == index);

		var caption = this.getIndexCaption(index);
		this.setCaption(caption);
		this.showCaption(caption);

		this.showRating(value);
	},

	showRating: function(value)
	{
		var base = Math.floor(value-1);
		var remainder = value - base-1;
		var halfMin = this.options.HalfRating["0"];
		var halfMax = this.options.HalfRating["1"];
		var index = remainder > halfMax ? base+1 : base;
		var hasHalf = remainder >= halfMin && remainder <= halfMax;
		for(var i = 0; i<this.radios.length; i++)
		{
			var node = this.radios[i].parentNode;
			var action = i > index ? 'removeClassName' : 'addClassName';
			Element[action](node, "rating_selected");
			if(i==index+1 && hasHalf)
				Element.addClassName(node, "rating_half");
			else
				Element.removeClassName(node, "rating_half");
			Element.removeClassName(node,"rating_hover");
		}
	},

	getIndexCaption : function(index)
	{
		return index > -1 ? this.radios[index].value : this.options.caption;
	},

	showCaption : function(value)
	{
		var caption = $(this.options.CaptionID);
		if(caption) caption.innerHTML = value;
		$(this.options.ListID).title = value;
	},

	setCaption : function(value)
	{
		this.options.caption = value;
		this.showCaption(value);
	},

	setEnabled : function(value)
	{
		this.enabled = value;
		for(var i = 0; i<this.radios.length; i++)
		{
			var action = value ? 'removeClassName' : 'addClassName'
			Element[action](this.radios[i].parentNode, "rating_disabled");
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
},

setCaption : function(id,value)
{
	Prado.WebUI.TRatingList.ratings[id].setCaption(value);
}
});