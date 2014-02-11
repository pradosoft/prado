jQuery.noConflict();

/**
 * TAutoComplete control.
 */
Prado.WebUI.TAutoComplete = jQuery.klass(Autocompleter.Base, Prado.WebUI.TActiveTextBox.prototype,
{
	initialize : function(options)
	{
		this.options = options;
		this.observers = new Array();
		this.hasResults = false;
		this.baseInitialize(options.ID, options.ResultPanel, options);
		Object.extend(this.options,
		{
			onSuccess : this.onComplete.bind(this)
		});

		if(options.AutoPostBack)
			this.onInit(options);

		Prado.Registry[options.ID] = this;
	},

	doCallback : function(options, event)
	{
		if(!this.active)
		{
			var request = new Prado.CallbackRequest(this.options.EventTarget, options);
			request.dispatch();
			event.preventDefault();
		}
	},

	 //Overrides parent implementation, fires onchange event.
	onClick: function(event)
	{
	    var element = Event.findElement(event, 'LI');
	    this.index = element.autocompleteIndex;
	    this.selectEntry();
	    this.hide();
	    jQuery(this.element).trigger('change');
	},

	getUpdatedChoices : function()
	{
		var options = new Array(this.getToken(),"__TAutoComplete_onSuggest__");
		Prado.Callback(this.options.EventTarget, options, null, this.options);
	},

	/**
	 * Overrides parent implements, don't update if no results.
	 */
	selectEntry: function()
	{
		if(this.hasResults)
		{
			this.active = false;
			this.updateElement(this.getCurrentEntry());
			var options = [this.index, "__TAutoComplete_onSuggestionSelected__"];
			Prado.Callback(this.options.EventTarget, options, null, this.options);
		}
	},

	onComplete : function(request, boundary)
  	{
  		var result = request.extractContent(boundary);
  		if(typeof(result) == "string")
		{
			if(result.length > 0)
			{
				this.hasResults = true;
				this.updateChoices(result);
			}
			else
			{
				this.active = false;
				this.hasResults = false;
				this.hide();
			}
		}
	}
});