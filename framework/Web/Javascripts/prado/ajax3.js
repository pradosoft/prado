/**
 * Prado Callback client-side request handler.
 */
Prado.Callback = Class.create();

/**
 * Static definitions.
 */
Object.extend(Prado.Callback,
{
	/**
	 * Callback request target POST field name.
	 */
	FIELD_CALLBACK_TARGET : 'PRADO_CALLBACK_TARGET',
	
	/**
	 * Callback request parameter POST field name.
	 */
	FIELD_CALLBACK_PARAMETER : 'PRADO_CALLBACK_PARAMETER',
	
	/**
	 * List of form fields that will be collected during callback.
	 */
	PostDataLoaders : ['PRADO_PAGESTATE'],
	
	/**
	 * Respond to Prado Callback request exceptions.
	 */
	Exception :
	{
		/**
		 * Server returns 505 exception. Just log it.
		 */
		"on505" : function(request, transport, data)
		{		
			var msg = 'HTTP '+transport.status+" with response";
			Logger.error(msg, transport.responseText);
			this.logException(data);
		},
		
		/**
		 * Callback OnComplete event,logs reponse and data to console.
		 */
		onComplete : function(request, transport, data)
		{
			if(transport.status != 505)
			{
				var msg = 'HTTP '+transport.status+" with response : \n";
				msg += transport.responseText + "\n";
				msg += "Data : \n"+inspect(data);
				Logger.warn(msg);
			}
		},
	
		/**
		 * Formats the exception message for display in console.
		 */
		formatException : function(e)
		{
			var msg = e.type + " with message \""+e.message+"\"";
			msg += " in "+e.file+"("+e.line+")\n";
			msg += "Stack trace:\n";
			var trace = e.trace;
			for(var i = 0; i<trace.length; i++)
			{
				msg += "  #"+i+" "+trace[i].file;
				msg += "("+trace[i].line+"): ";
				msg += trace[i]["class"]+"->"+trace[i]["function"]+"()"+"\n";
			}
			return msg;
		},
	
		/**
		 * Log Callback response exceptions to console.
		 */
		logException : function(e)
		{
			Logger.error("Callback Request Error "+e.code, this.formatException(e));
		}
	},
	
	/**
	 * @return string JSON encoded data.
	 */
	encode : function(data)
	{
		Prado.JSON.stringify(data);
	},
	
	/**
	 * @return mixed javascript data decoded from string using JSON decoding.
	 */
	decode : function(data)
	{
		return Prado.JSON.parse(data);
	}
})

//Add HTTP exception respones when logger is enabled.
Event.OnLoad(function()
{ 
	if(typeof Logger != "undefined") 
		Ajax.Responders.register(Prado.Callback.Exception);
});

/**
 * Create and prepare a new callback request.
 */
Prado.Callback.prototype = 
{
	/**
	 * Callback URL, same url as the current page.
	 */
	url : window.location.href,
	
	/**
	 * Callback options, including onXXX events.
	 */
	options : {},
	
	/**
	 * Callback target ID. E.g. $control->getUniqueID();
	 */
	id : null,
	
	/**
	 * Callback parameters.
	 */
	parameters : null,
	
	/**
	 * Prepare and inititate a callback request.
	 */
	initialize : function(id, parameters, onSuccess, options)
	{
		this.options = options || {};	
		this.id = id;
		this.parameters = parameters;
		
		var request = 
		{
			postBody : this._getPostData(),
			onSuccess : this._onSuccess.bind(this)
		}
		Object.extend(this.options || {},request);
		
		new Ajax.Request(this.url, this.options);
	},

	/**
	 * Collects the form inputs, encode the parameters, and sets the callback 
	 * target id. The resulting string is the request content body.
	 * @return string request body content containing post data.
	 */
	_getPostData : function()
	{
		var data = {};
		
		Prado.Callback.PostDataLoaders.each(function(name)
		{
			$A(document.getElementsByName(name)).each(function(element)
			{
				var value = $F(element);
				if(typeof(value) != "undefined")
					data[name] = value;
			})
		})
		if(typeof(this.parameters) != "undefined")
			data[Prado.Callback.FIELD_CALLBACK_PARAMETER] = Prado.Callback.encode(this.parameters);
		data[Prado.Callback.FIELD_CALLBACK_TARGET] = this.id;
		return $H(data).toQueryString();
	},
	
	/**
	 * Dispatch a successfull response to the appropriate responders.
	 */
	_onSuccess : function(response, transport, json)
	{
		//Logger.info("asd");	
	}
}