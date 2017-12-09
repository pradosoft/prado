/*! PRADO TRatingList javascript file | github.com/pradosoft/prado */

Prado.WebUI.TRatingList = jQuery.klass(Prado.WebUI.Control,
{
	selectedIndex : -1,
	rating: -1,
	readOnly : false,

	onInit : function(options)
	{
		var cap = $('#'+options.CaptionID).get(0);
		this.options = jQuery.extend(
		{
			caption : cap ? cap.innerHTML : ''
		}, options || {});

		this.radios = [];

		$('#'+options.ID).addClass(options.Style);
		for(var i = 0; i<options.ItemCount; i++)
		{
			var radio = $('#'+options.ID+"_c"+i).get(0);
			var td = radio.parentNode.parentNode;

			if(radio && td.tagName.toLowerCase()=='td')
			{
				this.radios.push(radio);
				$(td).addClass("rating");
			}
		}

		this.selectedIndex = options.SelectedIndex;
		this.rating = options.Rating;
		this.readOnly = options.ReadOnly
		if(options.Rating <= 0 && options.SelectedIndex >= 0)
			this.rating = options.SelectedIndex+1;
		this.setReadOnly(this.readOnly);
	},

	hover : function(index, ev)
	{
		if(this.readOnly==true) return;

		for(var i = 0; i<this.radios.length; i++)
		{
			var node = this.radios[i].parentNode.parentNode;
			if(i <= index)
				$(node).addClass('rating_hover');
			else
				$(node).removeClass('rating_hover');
			$(node).removeClass("rating_selected");
			$(node).removeClass("rating_half");
		}
		this.showCaption(this.getIndexCaption(index));
	},

	recover : function(index, ev)
	{
		if(this.readOnly==true) return;
		this.showRating(this.rating);
		this.showCaption(this.options.caption);
	},

	click : function(index, ev)
	{
		if(this.readOnly==true) return;
		this.selectedIndex = index;
		this.setRating(index+1);

		if(this.options['AutoPostBack']==true){
			this.dispatchRequest(ev);
		}
	},

	dispatchRequest : function(ev)
	{
		var requestOptions =jQuery.extend({}, this.options,
		{
			ID : this.options.ID+"_c"+this.selectedIndex,
			EventTarget : this.options.ListName+"$c"+this.selectedIndex
		});
		new Prado.PostBack(requestOptions, ev);
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

		this.showRating(this.rating);
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
			var node = this.radios[i].parentNode.parentNode;
			if(i <= index)
				$(node).addClass('rating_selected');
			else
				$(node).removeClass('rating_selected');

			if(i==index+1 && hasHalf)
				$(node).addClass("rating_half");
			else
				$(node).removeClass("rating_half");
			$(node).removeClass("rating_hover");
		}
	},

	getIndexCaption : function(index)
	{
		return index > -1 ? this.radios[index].value : this.options.caption;
	},

	showCaption : function(value)
	{
		$('#'+this.options.CaptionID).html(value);
		$('#'+this.options.ID).attr( "title", value);
	},

	setCaption : function(value)
	{
		this.options.caption = value;
		this.showCaption(value);
	},

	setReadOnly : function(value)
	{
		this.readOnly = value;
		for(var i = 0; i<this.radios.length; i++)
		{
			var node = this.radios[i].parentNode.parentNode;
			if(value)
			{
				$(node).addClass('rating_disabled');
				$(node).off('mouseover', jQuery.proxy(this.hover, this, i));
				$(node).off('mouseout', jQuery.proxy(this.recover, this, i));
				$(node).off('click', jQuery.proxy(this.click, this, i));
			} else {
				$(node).removeClass('rating_disabled');
				$(node).on('mouseover', jQuery.proxy(this.hover, this, i));
				$(node).on('mouseout', jQuery.proxy(this.recover, this, i));
				$(node).on('click', jQuery.proxy(this.click, this, i));
			}
		}

		this.showRating(this.rating);
	}
});

Prado.WebUI.TActiveRatingList = jQuery.klass(Prado.WebUI.TRatingList,
{
	dispatchRequest : function(ev)
	{
		var requestOptions =jQuery.extend({}, this.options,
		{
			ID : this.options.ID+"_c"+this.selectedIndex,
			EventTarget : this.options.ListName+"$c"+this.selectedIndex
		});
		var request = new Prado.CallbackRequest(requestOptions.EventTarget, requestOptions);
		if(request.dispatch()==false)
			ev.preventDefault();
	}

});
