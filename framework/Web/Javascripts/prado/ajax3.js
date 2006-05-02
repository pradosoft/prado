/**
 * Override Prototype's response implementation.
 */
Object.extend(Ajax.Request.prototype,
{
	/**
	 * Customize the response, dispatch onXXX response code events, and
	 * tries to execute response actions (javascript statements).
	 */
	respondToReadyState : function(readyState) 
	{
	    var event = Ajax.Request.Events[readyState];
	    var transport = this.transport, json = this.getHeaderData(Prado.CallbackRequest.DATA_HEADER);
		
	    if (event == 'Complete') 
	    {
			Ajax.Responders.dispatch('on' + transport.status, this, transport, json);
			Prado.CallbackRequest.dispatchActions(this.getHeaderData(Prado.CallbackRequest.ACTION_HEADER));
	      
	      try {
	        (this.options['on' + this.transport.status]
	         || this.options['on' + (this.responseIsSuccess() ? 'Success' : 'Failure')]
	         || Prototype.emptyFunction)(transport, json);
	  	      } catch (e) {
	        this.dispatchException(e);
	      }
	      if ((this.header('Content-type') || '').match(/^text\/javascript/i))
	        this.evalResponse();
	    }
	    
	    try {
	      (this.options['on' + event] || Prototype.emptyFunction)(transport, json);
	      Ajax.Responders.dispatch('on' + event, this, transport, json);
	    } catch (e) {
	      this.dispatchException(e);
	    }
	    
	    /* Avoid memory leak in MSIE: clean up the oncomplete event handler */
	    if (event == 'Complete')
	      this.transport.onreadystatechange = Prototype.emptyFunction;
	},
	
	/**
	 * Gets header data assuming JSON encoding.
	 * @param string header name
	 * @return object header data as javascript structures.
	 */
	getHeaderData : function(name)
	{
		try 
		{
			var json = this.header(name);
			return eval('(' + json + ')');
		} 
		catch (e) 
		{
			if(typeof(json) == "string")
			{
				Logger.info("using json")
				return Prado.CallbackRequest.decode(json);
			}
		}
	}
});

/**
 * Prado Callback client-side request handler.
 */
Prado.CallbackRequest = Class.create();

/**
 * Static definitions.
 */
Object.extend(Prado.CallbackRequest,
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
	 * Response data header name.
	 */
	DATA_HEADER : 'X-PRADO-DATA',
	/**
	 * Response javascript execution statement header name.
	 */
	ACTION_HEADER : 'X-PRADO-ACTIONS',
	/**
	 * Response errors/exceptions header name.
	 */
	ERROR_HEADER : 'X-PRADO-ERROR',
	
	/**
	 * Dispatch callback response actions.
	 */
	dispatchActions : function(actions)
	{
		actions.each(this.__run);
	},
	
	/**
	 * Prase and evaluate a Callback clien-side action
	 */
	__run : function(command)
	{
		for(var method in command)
		{
			if(command[method][0])
			{
				var id = command[method][0];
				if($(id) || id.indexOf("[]") > -1)
					method.toFunction().apply(this,command[method]);
				else if(typeof(Logger) != "undefined")
				{
					Logger.error("Error in executing callback response:", 
					"Unable to find HTML element with ID '"+id+"' before executing "+method+"().");		
				}
			}
		}
	},
	
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
			var e = request.getHeaderData(Prado.CallbackRequest.ERROR_HEADER);
			Logger.error("Callback Server Error "+e.code, this.formatException(e));
		},
		
		/**
		 * Callback OnComplete event,logs reponse and data to console.
		 */
		'on200' : function(request, transport, data)
		{
			if(transport.status < 500)
			{
				var msg = 'HTTP '+transport.status+" with response : \n";
				msg += transport.responseText + "\n";
				msg += "Data : \n"+inspect(data)+"\n";
				msg += "Actions : \n";
				request.getHeaderData(Prado.CallbackRequest.ACTION_HEADER).each(function(action)
				{
					msg += inspect(action)+"\n";
				})
				
				Logger.warn(msg);
			}
		},
	
		/**
		 * Uncaught exceptions during callback response.
		 */
		onException : function(e)
		{
			Logger.error('Uncaught Callback Client Exception:', e);
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
			msg += e.version+" "+e.time+"\n";
			return msg;
		}
	},
	
	/**
	 * @return string JSON encoded data.
	 */
	encode : function(data)
	{
		return Prado.JSON.stringify(data);
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
		Ajax.Responders.register(Prado.CallbackRequest.Exception);
});

/**
 * Create and prepare a new callback request.
 */
Prado.CallbackRequest.prototype = 
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
	 * Current callback request.
	 */
	request : null,
	
	/**
	 * Prepare and inititate a callback request.
	 */
	initialize : function(id, options)
	{
		this.options = options || {};	
		this.id = id;
		
		var request = 
		{
			postBody : this._getPostData(),
			parameters : ''
		}
		Object.extend(this.options || {},request);
		if(this.options.CausesValidation != false && typeof(Prado.Validation) != "undefined")
		{
			var form =  this.options.Form || Prado.Validation.getForm();
			if(Prado.Validation.validate(form,this.options.ValidationGroup,this) == false)
				return;
		}
		this.request = new Ajax.Request(this.url, this.options);
	},

	/**
	 * Collects the form inputs, encode the parameters, and sets the callback 
	 * target id. The resulting string is the request content body.
	 * @return string request body content containing post data.
	 */
	_getPostData : function()
	{
		var data = {};
		
		Prado.CallbackRequest.PostDataLoaders.each(function(name)
		{
			$A(document.getElementsByName(name)).each(function(element)
			{
				var value = $F(element);
				if(typeof(value) != "undefined")
					data[name] = value;
			})
		})
		if(typeof(this.options.params) != "undefined")
			data[Prado.CallbackRequest.FIELD_CALLBACK_PARAMETER] = Prado.CallbackRequest.encode(this.options.params);
		data[Prado.CallbackRequest.FIELD_CALLBACK_TARGET] = this.id;
		return $H(data).toQueryString();
	}
}

/**
 * Create a new callback request using default settings.
 * @param string callback handler unique ID.
 * @param mixed parameter to pass to callback handler on the server side.
 * @param function client side onSuccess event handler.
 * @param object additional request options.
 * @return boolean always false.
 */
Prado.Callback = function(UniqueID, parameter, onSuccess, options)
{
	var callback =  
	{
		'params' : parameter || '',
		'onSuccess' : onSuccess || Prototype.emptyFunction, 
		'CausesValidation' : true
	};

	Object.extend(callback, options || {});
	
	new Prado.CallbackRequest(UniqueID, callback);
	return false;
}
