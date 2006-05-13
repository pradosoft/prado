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
 * TAutoComplete control.
 */
Prado.WebUI.TAutoComplete = Class.extend(Autocompleter.Base,
{
	initialize : function(options)
	{
		this.options = options;
		this.baseInitialize(options.ID, options.ResultPanel, options);
		Object.extend(this.options, 
		{
			onSuccess : this.onComplete.bind(this)
		});
	},
	
	getUpdatedChoices : function()
	{
		Prado.Callback(this.options.EventTarget, this.getToken(), null, this.options);
	},
	
  	onComplete : function(request, boundary) 
  	{
  		result = Prado.Element.extractContent(request.responseText, boundary);
  		if(typeof(result) == "string" && result.length > 0)
			this.updateChoices(result);
	}	
});