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
	      try
	      {
	      	Prado.CallbackRequest.updatePageState(this,transport);
			Ajax.Responders.dispatch('on' + transport.status, this, transport, json);
			Prado.CallbackRequest.dispatchActions(transport,this.getHeaderData(Prado.CallbackRequest.ACTION_HEADER));

	        (this.options['on' + this.transport.status]
	         || this.options['on' + (this.responseIsSuccess() ? 'Success' : 'Failure')]
	         || Prototype.emptyFunction)(this, json);
	  	      } catch (e) {
	        this.dispatchException(e);
	      }
	      if ((this.header('Content-type') || '').match(/^text\/javascript/i))
	        this.evalResponse();
	    }

	    try {
	      (this.options['on' + event] || Prototype.emptyFunction)(this, json);
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
				return Prado.CallbackRequest.decode(json);
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
	 * Callback request page state field name,
	 */
	FIELD_CALLBACK_PAGESTATE : 'PRADO_PAGESTATE',

	FIELD_POSTBACK_TARGET : 'PRADO_POSTBACK_TARGET',

	FIELD_POSTBACK_PARAMETER : 'PRADO_POSTBACK_PARAMETER',

	/**
	 * List of form fields that will be collected during callback.
	 */
	PostDataLoaders : [],
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
	 * Page state header name.
	 */
	PAGESTATE_HEADER : 'X-PRADO-PAGESTATE',
	/**
	 * Current requests in progress.
	 */
	requestInProgress : null,

	/**
	 * Add ids of inputs element to post in the request.
	 */
	addPostLoaders : function(ids)
	{
		this.PostDataLoaders = this.PostDataLoaders.concat(ids);
		list = [];
		this.PostDataLoaders.each(function(id)
		{
			if(list.indexOf(id) < 0)
				list.push(id);
		});
		this.PostDataLoaders = list;
	},

	/**
	 * Dispatch callback response actions.
	 */
	dispatchActions : function(transport,actions)
	{
		if(actions && actions.length > 0)
			actions.each(this.__run.bind(this,transport));
	},

	/**
	 * Prase and evaluate a Callback clien-side action
	 */
	__run : function(transport, command)
	{
		for(var method in command)
		{
			try
			{
				method.toFunction().apply(this,command[method].concat(transport));
			}
			catch(e)
			{
				if(typeof(Logger) != "undefined")
					Prado.CallbackRequest.Exception.onException(null,e);
			}
		}
	},

	/**
	 * Respond to Prado Callback request exceptions.
	 */
	Exception :
	{
		/**
		 * Server returns 500 exception. Just log it.
		 */
		"on500" : function(request, transport, data)
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
				data = request.getHeaderData(Prado.CallbackRequest.ACTION_HEADER);
				if(data && data.length > 0)
				{
					data.each(function(action)
					{
						msg += inspect(action)+"\n";
					});
				}
				Logger.warn(msg);
			}
		},

		/**
		 * Uncaught exceptions during callback response.
		 */
		onException : function(request,e)
		{
			msg = "";
			$H(e).each(function(item)
			{
				msg += item.key+": "+item.value+"\n";
			})
			Logger.error('Uncaught Callback Client Exception:', msg);
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
		if(typeof(data) == "string" && data.trim().length > 0)
			return Prado.JSON.parse(data);
		else
			return null;
	},

	/**
	 * Dispatch a priority request, it will call abortRequestInProgress first.
	 */
	dispatchPriorityRequest : function(callback)
	{
		//Logger.info("priority request "+callback.id)
		this.abortRequestInProgress();

		callback.request = new Ajax.Request(callback.url, callback.options);
		callback.timeout = setTimeout(function()
		{
		//	Logger.warn("priority timeout");
			Prado.CallbackRequest.abortRequestInProgress();
		},callback.options.RequestTimeOut);

		this.requestInProgress = callback;
		return true;
		//Logger.info("dispatched "+this.requestInProgress)
	},

	/**
	 * Dispatch a normal request, no timeouts or aborting of requests.
	 */
	dispatchNormalRequest : function(callback)
	{
	//	Logger.info("dispatching normal request");
		new Ajax.Request(callback.url, callback.options);
		return true;
	},

	/**
	 * Abort the current priority request in progress.
	 */
	abortRequestInProgress : function()
	{
		inProgress = Prado.CallbackRequest.requestInProgress;
		//Logger.info("aborting ... "+inProgress);
		if(inProgress)
		{
		//	Logger.warn("aborted "+inProgress.id)
			inProgress.request.transport.abort();
			clearTimeout(inProgress.timeout);
			Prado.CallbackRequest.requestInProgress = null;
			return true;
		}
		return false;
	},

	/**
	 * Updates the page state. It will update only if EnablePageStateUpdate and
	 * HasPriority options are both true.
	 */
	updatePageState : function(request, transport)
	{
		pagestate = $(this.FIELD_CALLBACK_PAGESTATE);
		if(request.options.EnablePageStateUpdate && request.options.HasPriority && pagestate)
		{
			data = request.header(this.PAGESTATE_HEADER);
			if(typeof(data) == "string" && data.length > 0)
				pagestate.value = data;
			else
			{
				if(typeof(Logger) != "undefined")
					Logger.debug("Bad page state:"+data);
			}
		}
	}
})

/**
 * Automatically aborts the current request when a priority request has returned.
 */
Ajax.Responders.register({onComplete : function(request)
{
	if(request.options.HasPriority)
		Prado.CallbackRequest.abortRequestInProgress();
}});

//Add HTTP exception respones when logger is enabled.
Event.OnLoad(function()
{
	if(typeof Logger != "undefined")
		Ajax.Responders.register(Prado.CallbackRequest.Exception);
});

/**
 * Create and prepare a new callback request.
 * Call the dispatch() method to start the callback request.
 * <code>
 * request = new Prado.CallbackRequest(UniqueID, callback);
 * request.dispatch();
 * </code>
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
	options : {	},

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
		this.id = id;
		this.options = Object.extend(
		{
			RequestTimeOut : 30000, // 30 second timeout.
			EnablePageStateUpdate : true,
			HasPriority : true,
			CausesValidation : true,
			ValidationGroup : null,
			PostInputs : true
		}, options || {});
	},

	/**
	 * Sets the request parameter
	 * @param {Object} parameter value
	 */
	setParameter : function(value)
	{
		this.options['params'] = value;
	},

	/**
	 * @return {Object} request paramater value.
	 */
	getParameter : function()
	{
		return this.options['params'];
	},

	/**
	 * Sets the callback request timeout.
	 * @param {integer} timeout in  milliseconds
	 */
	setRequestTimeOut : function(timeout)
	{
		this.options['RequestTimeOut'] = timeout;
	},

	/**
	 * @return {integer} request timeout in milliseconds
	 */
	getRequestTimeOut : function()
	{
		return this.options['RequestTimeOut'];
	},

	/**
	 * Set true to enable validation on callback dispatch.
	 * @param {boolean} true to validate
	 */
	setCausesValidation : function(validate)
	{
		this.options['CausesValidation'] = validate;
	},

	/**
	 * @return {boolean} validate on request dispatch
	 */
	getCausesValidation : function()
	{
		return this.options['CausesValidation'];
	},

	/**
	 * Sets the validation group to validate during request dispatch.
	 * @param {string} validation group name
	 */
	setValidationGroup : function(group)
	{
		this.options['ValidationGroup'] = group;
	},

	/**
	 * @return {string} validation group name.
	 */
	getValidationGroup : function()
	{
		return this.options['ValidationGroup'];
	},

	/**
	 * Dispatch the callback request.
	 */
	dispatch : function()
	{
		//override parameter and postBody options.
		Object.extend(this.options,
		{
			postBody : this._getPostData(),
			parameters : ''
		});

		if(this.options.CausesValidation && typeof(Prado.Validation) != "undefined")
		{
			var form =  this.options.Form || Prado.Validation.getForm();
			if(Prado.Validation.validate(form,this.options.ValidationGroup,this) == false)
				return false;
		}

		if(this.options.HasPriority)
			return Prado.CallbackRequest.dispatchPriorityRequest(this);
		else
			return Prado.CallbackRequest.dispatchNormalRequest(this);
	},

	/**
	 * Collects the form inputs, encode the parameters, and sets the callback
	 * target id. The resulting string is the request content body.
	 * @return string request body content containing post data.
	 */
	_getPostData : function()
	{
		var data = {};
		var callback = Prado.CallbackRequest;
		if(this.options.PostInputs != false)
		{
			callback.PostDataLoaders.each(function(name)
			{
				$A(document.getElementsByName(name)).each(function(element)
				{
					//IE will try to get elements with ID == name as well.
					if(element.type && element.name == name)
					{
						value = $F(element);
						if(typeof(value) != "undefined")
							data[name] = value;
					}
				})
			})
		}
		if(typeof(this.options.params) != "undefined")
			data[callback.FIELD_CALLBACK_PARAMETER] = callback.encode(this.options.params);
		var pageState = $F(callback.FIELD_CALLBACK_PAGESTATE);
		if(typeof(pageState) != "undefined")
			data[callback.FIELD_CALLBACK_PAGESTATE] = pageState;
		data[callback.FIELD_CALLBACK_TARGET] = this.id;
		if(this.options.EventTarget)
			data[callback.FIELD_POSTBACK_TARGET] = this.options.EventTarget;
		if(this.options.EventParameter)
			data[callback.FIELD_POSTBACK_PARAMETER] = this.options.EventParameter;
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
		'onSuccess' : onSuccess || Prototype.emptyFunction
	};

	Object.extend(callback, options || {});

	request = new Prado.CallbackRequest(UniqueID, callback);
	request.dispatch();
	return false;
}
