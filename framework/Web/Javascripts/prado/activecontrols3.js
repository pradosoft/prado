/**
 * Generic postback control.
 */
Prado.WebUI.CallbackControl = Class.extend(Prado.WebUI.PostBackControl,
{
	onPostBack : function(event, options)
	{
		new Prado.CallbackRequest(options.EventTarget, options);
		Event.stop(event);
	}	
});

/**
 * TActiveButton control.
 */
Prado.WebUI.TActiveButton = Class.extend(Prado.WebUI.CallbackControl);

/**
 * TActiveTextBox control, handles onchange event.
 */
Prado.WebUI.TActiveTextBox = Class.extend(Prado.WebUI.TTextBox,
{
	onInit : function(options)
	{
		if(options['TextMode'] != 'MultiLine')
			Event.observe(this.element, "keydown", this.handleReturnKey.bind(this));
		Event.observe(this.element, "change", this.doCallback.bindEvent(this,options));
	},
	
	doCallback : function(event, options)
	{
		new Prado.CallbackRequest(options.EventTarget, options);
		Event.stop(event);
	}
});

/**
 * TAutoComplete control.
 */
Prado.WebUI.TAutoComplete = Class.extend(Autocompleter.Base, Prado.WebUI.TActiveTextBox.prototype);
Prado.WebUI.TAutoComplete = Class.extend(Prado.WebUI.TAutoComplete,
{
	initialize : function(options)
	{
		this.options = options;
		this.baseInitialize(options.ID, options.ResultPanel, options);
		Object.extend(this.options, 
		{
			onSuccess : this.onComplete.bind(this)
		});
		
		if(options.AutoPostBack)
			this.onInit(options);
	},

	doCallback : function(event, options)
	{
		if(!this.active)
		{
			new Prado.CallbackRequest(options.EventTarget, options);
			Event.stop(event);
		}
	},
	
	 //Overrides parent implementation, fires onchange event.
	onClick: function(event) 
	{
	    var element = Event.findElement(event, 'LI');
	    this.index = element.autocompleteIndex;
	    this.selectEntry();
	    this.hide();
		Event.fireEvent(this.element, "change");
	},
	
	getUpdatedChoices : function()
	{
		options = new Array(this.getToken(),"__TAutComplete_onSuggest__");
		Prado.Callback(this.options.EventTarget, options, null, this.options);
	},
	
  	onComplete : function(request, boundary) 
  	{
  		result = Prado.Element.extractContent(request.responseText, boundary);
  		if(typeof(result) == "string" && result.length > 0)
			this.updateChoices(result);
	}	
});