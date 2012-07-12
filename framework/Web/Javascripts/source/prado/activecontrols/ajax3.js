
Prado.AjaxRequest = Class.create();
Prado.AjaxRequest.prototype = Object.clone(Ajax.Request.prototype);

/**
 * Override Prototype's response implementation.
 */
Object.extend(Prado.AjaxRequest.prototype,
{
	/*initialize: function(request)
	{
		this.CallbackRequest = request;
		this.transport = Ajax.getTransport();
		this.setOptions(request.options);
		this.request(request.url);
	},*/

	/**
	 * Customize the response, dispatch onXXX response code events, and
	 * tries to execute response actions (javascript statements).
	 */
	respondToReadyState : function(readyState)
	{
	    var event = Ajax.Request.Events[readyState];
	    var transport = this.transport, json = this.getBodyDataPart(Prado.CallbackRequest.DATA_HEADER);

	    if (event == 'Complete')
	    {
		var redirectUrl = this.getBodyContentPart(Prado.CallbackRequest.REDIRECT_HEADER);
		if (redirectUrl)
	    		document.location.href = redirectUrl;

		if ((this.getHeader('Content-type') || '').match(/^text\/javascript/i))
		{
	        	try
			{
	          		json = eval('(' + transport.responseText + ')');
		        }
			catch (e)
			{
				if(typeof(json) == "string")
					json = Prado.CallbackRequest.decode(result);
			}
		}

		try
		{
			Prado.CallbackRequest.updatePageState(this,transport);
			Prado.CallbackRequest.checkHiddenFields(this,transport);
			var obj = this;
			Prado.CallbackRequest.loadAssets(this,transport, function()

				{
					try
					{
						Ajax.Responders.dispatch('on' + transport.status, obj, transport, json);
						Prado.CallbackRequest.dispatchActions(transport,obj.getBodyDataPart(Prado.CallbackRequest.ACTION_HEADER));

	        				(
							obj.options['on' + obj.transport.status]
	         					|| 
							obj.options['on' + (obj.success() ? 'Success' : 'Failure')]
	         					|| 
							Prototype.emptyFunction
						) (obj, json);
					}
					catch (e)
					{
	        				obj.dispatchException(e);
					}
				}
			);
	      } 
	      catch (e) 
              {
	        this.dispatchException(e);
	      }
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
		return this.getJsonData(this.getHeader(name));
	},

	getBodyContentPart : function(name)
	{
		if(typeof(this.transport.responseText)=="string")
			return Prado.Element.extractContent(this.transport.responseText, name);
	},

	getJsonData : function(json)
	{
		try
		{
			return eval('(' + json + ')');
		}
		catch (e)
		{
			if(typeof(json) == "string")
				return Prado.CallbackRequest.decode(json);
		}
	},

	getBodyDataPart : function(name)
	{
		return this.getJsonData(this.getBodyContentPart(name));
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
	 * Script list header name.
	 */
	SCRIPTLIST_HEADER : 'X-PRADO-SCRIPTLIST',
	/**
	 * Stylesheet list header name.
	 */
	STYLESHEETLIST_HEADER : 'X-PRADO-STYLESHEETLIST',
	/**
	 * Hidden field list header name.
	 */
	HIDDENFIELDLIST_HEADER : 'X-PRADO-HIDDENFIELDLIST',

	REDIRECT_HEADER : 'X-PRADO-REDIRECT',

	requestQueue : [],

	//all request objects
	requests : {},

	getRequestById : function(id)
	{
		var requests = Prado.CallbackRequest.requests;
		if(typeof(requests[id]) != "undefined")
			return requests[id];
	},

	dispatch : function(id)
	{
		var requests = Prado.CallbackRequest.requests;
		if(typeof(requests[id]) != "undefined")
			requests[id].dispatch();
	},

	/**
	 * Add ids of inputs element to post in the request.
	 */
	addPostLoaders : function(ids)
	{
		var self = Prado.CallbackRequest;
		self.PostDataLoaders = self.PostDataLoaders.concat(ids);
		var list = [];
		self.PostDataLoaders.each(function(id)
		{
			if(list.indexOf(id) < 0)
				list.push(id);
		});
		self.PostDataLoaders = list;
	},

	/**
	 * Dispatch callback response actions.
	 */
	dispatchActions : function(transport,actions)
	{
		var self = Prado.CallbackRequest;
		if(actions && actions.length > 0)
			actions.each(self.__run.bind(self,transport));
	},

	/**
	 * Prase and evaluate a Callback clien-side action
	 */
	__run : function(transport, command)
	{
		var self = Prado.CallbackRequest;
		self.transport = transport;
		for(var method in command)
		{
			try
			{
				method.toFunction().apply(self,command[method]);
			}
			catch(e)
			{
				if(typeof(Logger) != "undefined")
					self.Exception.onException(null,e);
				else
					debugger;
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
			if (e)
				Logger.error("Callback Server Error "+e.code, this.formatException(e));
			else
				Logger.error("Callback Server Error Unknown",'');
		},

		/**
		 * Callback OnComplete event,logs reponse and data to console.
		 */
		'on200' : function(request, transport, data)
		{
			if(transport.status < 500)
			{
				var msg = 'HTTP '+transport.status+" with response : \n";
				if(transport.responseText.trim().length >0)
				{
					var f = RegExp('(<!--X-PRADO[^>]+-->)([\\s\\S\\w\\W]*)(<!--//X-PRADO[^>]+-->)',"m");
					msg += transport.responseText.replace(f,'') + "\n";
				}
				if(typeof(data)!="undefined" && data != null)
					msg += "Data : \n"+inspect(data)+"\n";
				data = request.getBodyDataPart(Prado.CallbackRequest.ACTION_HEADER);
				if(data && data.length > 0)
				{
					msg += "Actions : \n";
					data.each(function(action)
					{
						msg += inspect(action)+"\n";
					});
				}
				Logger.info(msg);
			}
		},

		/**
		 * Uncaught exceptions during callback response.
		 */
		onException : function(request,e)
		{
			var msg = "";
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
	 * Dispatch a normal request, no timeouts or aborting of requests.
	 */
	dispatchNormalRequest : function(callback)
	{
		callback.options.postBody = callback._getPostData(),
		callback.request(callback.url);
		return true;
	},

	/**
	 * Abort the current priority request in progress.
	 */
	tryNextRequest : function()
	{
		var self = Prado.CallbackRequest;
		//Logger.debug('trying next request');
		if(typeof(self.currentRequest) == 'undefined' || self.currentRequest==null)
		{
			if(self.requestQueue.length > 0)
				return self.dispatchQueue();
			//else
				//Logger.warn('empty queque');
		}
		//else
			//Logger.warn('current request ' + self.currentRequest.id);
	},

	/*
	 * Checks which scripts are used by the response and ensures they're loaded 
	 */
	loadScripts : function(request, transport, callback)
	{
		var self = Prado.CallbackRequest;
		var data = request.getBodyContentPart(self.SCRIPTLIST_HEADER);
		if (!this.ScriptsToLoad) this.ScriptsToLoad = new Array();
		this.ScriptLoadFinishedCallback = callback;
		if (typeof(data) == "string" && data.length > 0)
		{
		  	json = Prado.CallbackRequest.decode(data);
			if(typeof(json) != "object")
				Logger.warn("Invalid script list:"+data);
			else
				for(var key in json)
					if (/^\d+$/.test(key))
					{
						var url = json[key];
						if (!Prado.ScriptManager.isAssetLoaded(url))
							this.ScriptsToLoad.push(url);
					}
		}
		this.loadNextScript();
	},

	loadNextScript: function()
	{
		var done = (!this.ScriptsToLoad || (this.ScriptsToLoad.length==0));
		if (!done)
			{
				var url = this.ScriptsToLoad.shift(); var obj = this;
				if (
					Prado.ScriptManager.ensureAssetIsLoaded(url, 
						function() { 
							obj.loadNextScript(); 
						} 
					)
				   )
				   this.loadNextScript();
			}
		else
			{
				if (this.ScriptLoadFinishedCallback)
				{
					var cb = this.ScriptLoadFinishedCallback;
					this.ScriptLoadFinishedCallback = null;
					cb();
				}
			}
	},

	loadStyleSheetsAsync : function(request, transport)
	{
		var self = Prado.CallbackRequest;
		var data = request.getBodyContentPart(self.STYLESHEETLIST_HEADER);
		if (typeof(data) == "string" && data.length > 0)
		{
		  	json = Prado.CallbackRequest.decode(data);
			if(typeof(json) != "object")
				Logger.warn("Invalid stylesheet list:"+data);
			else
				for(var key in json)
					if (/^\d+$/.test(key))
						Prado.StyleSheetManager.ensureAssetIsLoaded(json[key],null);
		}
	},

	loadStyleSheets : function(request, transport, callback)
	{
		var self = Prado.CallbackRequest;
		var data = request.getBodyContentPart(self.STYLESHEETLIST_HEADER);
		if (!this.StyleSheetsToLoad) this.StyleSheetsToLoad = new Array();
		this.StyleSheetLoadFinishedCallback = callback;
		if (typeof(data) == "string" && data.length > 0)
		{
		  	json = Prado.CallbackRequest.decode(data);
			if(typeof(json) != "object")
				Logger.warn("Invalid stylesheet list:"+data);
			else
				for(var key in json)
					if (/^\d+$/.test(key))
					{
						var url = json[key];
						if (!Prado.StyleSheetManager.isAssetLoaded(url))
							this.StyleSheetsToLoad.push(url);
					}
		}
		this.loadNextStyleSheet();
	},

	loadNextStyleSheet: function()
	{
		var done = (!this.StyleSheetsToLoad || (this.StyleSheetsToLoad.length==0));
		if (!done)
			{
				var url = this.StyleSheetsToLoad.shift(); var obj = this;
				if (
					Prado.StyleSheetManager.ensureAssetIsLoaded(url, 
						function() { 
							obj.loadNextStyleSheet(); 
						} 
					)
				   )
				   this.loadNextStyleSheet();
			}
		else
			{
				if (this.StyleSheetLoadFinishedCallback)
				{
					var cb = this.StyleSheetLoadFinishedCallback;
					this.StyleSheetLoadFinishedCallback = null;
					cb();
				}
			}
	},

	/*
	 * Checks which assets are used by the response and ensures they're loaded 
	 */
	loadAssets : function(request, transport, callback)
	{
		/*

		  ! This is the callback-based loader for stylesheets, which loads them one-by-one, and
		  ! waits for all of them to be loaded before loading scripts and processing the rest of
		  ! the callback.
		  !
		  ! That however is not neccessary, as stylesheets can be loaded asynchronously too.
		  !
		  ! I leave this code here for the case that this turns out to be a compatibility issue
		  ! (for ex. I can imagine some scripts trying to access stylesheet properties and such)
		  ! so if need can be reactivated. If you do so, comment out the async stylesheet loader below!

		var obj = this;
		this.loadStyleSheets(request,transport, function() {
			obj.loadScripts(request,transport,callback);
		});

		*/

		this.loadStyleSheetsAsync(request,transport);

		this.loadScripts(request,transport,callback);
	},

	checkHiddenField: function(name, value)
	{
		var id = name.replace(':','_');
		if (!document.getElementById(id))
		{
   			var field = document.createElement('input');
			field.setAttribute('type','hidden');
			field.id = id;
			field.name = name;
			field.value = value;
			document.body.appendChild(field);
		}
	},

	checkHiddenFields : function(request, transport)
	{
		var self = Prado.CallbackRequest;
		var data = request.getBodyContentPart(self.HIDDENFIELDLIST_HEADER);
		if (typeof(data) == "string" && data.length > 0)
		{
		  	json = Prado.CallbackRequest.decode(data);
			if(typeof(json) != "object")
				Logger.warn("Invalid hidden field list:"+data);
			else
				for(var key in json)
					this.checkHiddenField(key,json[key]);
		}
	},

	/**
	 * Updates the page state. It will update only if EnablePageStateUpdate and
	 * HasPriority options are both true.
	 */
	updatePageState : function(request, transport)
	{
		var self = Prado.CallbackRequest;
		var pagestate = $(self.FIELD_CALLBACK_PAGESTATE);
		var enabled = request.ActiveControl.EnablePageStateUpdate && request.ActiveControl.HasPriority;
		var aborted = typeof(self.currentRequest) == 'undefined' || self.currentRequest == null;
		if(enabled && !aborted && pagestate)
		{
			var data = request.getBodyContentPart(self.PAGESTATE_HEADER);
			if(typeof(data) == "string" && data.length > 0)
				pagestate.value = data;
			else
			{
				if(typeof(Logger) != "undefined")
					Logger.warn("Missing page state:"+data);
				//Logger.warn('## bad state: setting current request to null');
				self.endCurrentRequest();
				//self.tryNextRequest();
				return false;
			}
		}
		self.endCurrentRequest();
		//Logger.warn('## state updated: setting current request to null');
		//self.tryNextRequest();
		return true;
	},

	enqueue : function(callback)
	{
		var self = Prado.CallbackRequest;
		self.requestQueue.push(callback);
		//Logger.warn("equeued "+callback.id+", current queque length="+self.requestQueue.length);
		self.tryNextRequest();
	},

	dispatchQueue : function()
	{
		var self = Prado.CallbackRequest;
		//Logger.warn("dispatching queque, length="+self.requestQueue.length+" request="+self.currentRequest);
		var callback = self.requestQueue.shift();
		self.currentRequest = callback;

		//get data
		callback.options.postBody = callback._getPostData(),

		//callback.request = new Prado.AjaxRequest(callback);
		callback.timeout = setTimeout(function()
		{
			//Logger.warn("priority timeout");
			self.abortRequest(callback.id);
		},callback.ActiveControl.RequestTimeOut);
		callback.request(callback.url);
		//Logger.debug("dispatched "+self.currentRequest.id + " ...")
	},

	endCurrentRequest : function()
	{
		var self = Prado.CallbackRequest;
		if(typeof(self.currentRequest) != 'undefined' && self.currentRequest != null)
			clearTimeout(self.currentRequest.timeout);
		self.currentRequest=null;
	},

	abortRequest : function(id)
	{
		//Logger.warn("abort id="+id);
		var self = Prado.CallbackRequest;
		if(typeof(self.currentRequest) != 'undefined'
			&& self.currentRequest != null && self.currentRequest.id == id)
		{
			var request = self.currentRequest;
			if(request.transport.readyState < 4)
				request.transport.abort();
			//Logger.warn('## aborted: setting current request to null');
			self.endCurrentRequest();
		}
		self.tryNextRequest();
	}
});

/**
 * Automatically aborts the current request when a priority request has returned.
 */
Ajax.Responders.register({onComplete : function(request)
{
	if(request && request instanceof Prado.AjaxRequest)
	{
		if(request.ActiveControl.HasPriority)
			Prado.CallbackRequest.tryNextRequest();
	}
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
Prado.CallbackRequest.prototype = Object.extend(Prado.AjaxRequest.prototype,
{

	/**
	 * Prepare and inititate a callback request.
	 */
	initialize : function(id, options)
	{
		/**
		 * Callback URL, same url as the current page.
		 */
		this.url = this.getCallbackUrl();
		
		this.transport = Ajax.getTransport();
		this.Enabled = true;
		this.id = id;
		this.randomId = this.randomString();
		
		if(typeof(id)=="string"){
			Prado.CallbackRequest.requests[id+"__"+this.randomId] = this;
		}
		
		this.setOptions(Object.extend(
		{
			RequestTimeOut : 30000, // 30 second timeout.
			EnablePageStateUpdate : true,
			HasPriority : true,
			CausesValidation : true,
			ValidationGroup : null,
			PostInputs : true
		}, options || {}));

		this.ActiveControl = this.options;
		Prado.CallbackRequest.requests[id+"__"+this.randomId].ActiveControl = this.options;
	},
	
	/**
	 * Sets the request options
	 * @return {Array} request options.
	 */
	setOptions: function(options){
		
		this.options = {
			method:       'post',
			asynchronous: true,
			contentType:  'application/x-www-form-urlencoded',
			encoding:     'UTF-8',
			parameters:   '',
			evalJSON:     true,
			evalJS:       true
		};
		
		Object.extend(this.options, options || { });

		this.options.method = this.options.method.toLowerCase();
		if(Object.isString(this.options.parameters)){
			this.options.parameters = this.options.parameters.toQueryParams();
		}
	},
	
	/**
	 * Gets the url from the forms that contains the PRADO_PAGESTATE
	 * @return {String} callback url.
	 */
	getCallbackUrl : function()
	{
		return $('PRADO_PAGESTATE').form.action;
	},

	/**
	 * Sets the request parameter
	 * @param {Object} parameter value
	 */
	setCallbackParameter : function(value)
	{
		var requestId = this.id+"__"+this.randomId;
		this.ActiveControl['CallbackParameter'] = value;
		Prado.CallbackRequest.requests[requestId].ActiveControl['CallbackParameter'] = value;
	},

	/**
	 * @return {Object} request paramater value.
	 */
	getCallbackParameter : function()
	{
		return Prado.CallbackRequest.requests[this.id+"__"+this.randomId].ActiveControl['CallbackParameter'];
	},

	/**
	 * Sets the callback request timeout.
	 * @param {integer} timeout in  milliseconds
	 */
	setRequestTimeOut : function(timeout)
	{
		this.ActiveControl['RequestTimeOut'] = timeout;
	},

	/**
	 * @return {integer} request timeout in milliseconds
	 */
	getRequestTimeOut : function()
	{
		return this.ActiveControl['RequestTimeOut'];
	},

	/**
	 * Set true to enable validation on callback dispatch.
	 * @param {boolean} true to validate
	 */
	setCausesValidation : function(validate)
	{
		this.ActiveControl['CausesValidation'] = validate;
	},

	/**
	 * @return {boolean} validate on request dispatch
	 */
	getCausesValidation : function()
	{
		return this.ActiveControl['CausesValidation'];
	},

	/**
	 * Sets the validation group to validate during request dispatch.
	 * @param {string} validation group name
	 */
	setValidationGroup : function(group)
	{
		this.ActiveControl['ValidationGroup'] = group;
	},

	/**
	 * @return {string} validation group name.
	 */
	getValidationGroup : function()
	{
		return this.ActiveControl['ValidationGroup'];
	},

	/**
	 * Dispatch the callback request.
	 */
	dispatch : function()
	{
		//Logger.info("dispatching request");
		//trigger tinyMCE to save data.
		if(typeof tinyMCE != "undefined")
			tinyMCE.triggerSave();

		if(this.ActiveControl.CausesValidation && typeof(Prado.Validation) != "undefined")
		{
			var form =  this.ActiveControl.Form || Prado.Validation.getForm();
			if(Prado.Validation.validate(form,this.ActiveControl.ValidationGroup,this) == false)
				return false;
		}

		if(this.ActiveControl.onPreDispatch)
			this.ActiveControl.onPreDispatch(this,null);

		if(!this.Enabled)
			return;
	
		// Opera don't have onLoading/onLoaded state, so, simulate them just
		// before sending the request.
		if (Prototype.Browser.Opera)
		{
			if (this.ActiveControl.onLoading)
			{
				this.ActiveControl.onLoading(this,null);
				Ajax.Responders.dispatch('onLoading',this, this.transport,null);
			}
			if (this.ActiveControl.onLoaded)
			{
				this.ActiveControl.onLoaded(this,null);
				Ajax.Responders.dispatch('onLoaded',this, this.transport,null);
			}
		}
		
		var result;
		if(this.ActiveControl.HasPriority)
		{
			return Prado.CallbackRequest.enqueue(this);
			//return Prado.CallbackRequest.dispatchPriorityRequest(this);
		}
		else
			return Prado.CallbackRequest.dispatchNormalRequest(this);
	},

	abort : function()
	{
		return Prado.CallbackRequest.abortRequest(this.id);
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
		if(this.ActiveControl.PostInputs != false)
		{
			callback.PostDataLoaders.each(function(name)
			{
				var elements=$A(document.getElementsByName(name));
				if(elements.size() == 0)
				{
					name += '[]';
					elements=$A(document.getElementsByName(name));
				}
				elements.each(function(element)
				{
					//IE will try to get elements with ID == name as well.
					if(element.type && element.name == name)
					{
						var value = $F(element);
						if(typeof(value) != "undefined" && value != null)
							data[name] = value;
					}
				})
			})
		}
		if(typeof(this.ActiveControl.CallbackParameter) != "undefined")
			data[callback.FIELD_CALLBACK_PARAMETER] = callback.encode(this.getCallbackParameter());
		var pageState = $F(callback.FIELD_CALLBACK_PAGESTATE);
		if(typeof(pageState) != "undefined")
			data[callback.FIELD_CALLBACK_PAGESTATE] = pageState;
		data[callback.FIELD_CALLBACK_TARGET] = this.id;
		if(this.ActiveControl.EventTarget)
			data[callback.FIELD_POSTBACK_TARGET] = this.ActiveControl.EventTarget;
		if(this.ActiveControl.EventParameter)
			data[callback.FIELD_POSTBACK_PARAMETER] = this.ActiveControl.EventParameter;
		return $H(data).toQueryString();
	},

	/**
	 * Creates a random string with a length of 8 chars.
	 * @return string
	 */
	randomString : function()
	{
		chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
		randomString = "";
		for(x=0;x<8;x++)
			randomString += chars.charAt(Math.floor(Math.random() * 62));
		return randomString
	}
});

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
		'CallbackParameter' : parameter || '',
		'onSuccess' : onSuccess || Prototype.emptyFunction
	};

	Object.extend(callback, options || {});

	var request = new Prado.CallbackRequest(UniqueID, callback);
	request.dispatch();
	return false;
};



/**
 * Asset manager classes for lazy loading of scripts and stylesheets
 * @author Gabor Berczi (gabor.berczi@devworx.hu)
 */

if (typeof(Prado.AssetManagerClass)=="undefined") {

  Prado.AssetManagerClass = Class.create();
  Prado.AssetManagerClass.prototype = {

	initialize: function() {
		this.loadedAssets = new Array();
		this.discoverLoadedAssets();
	},

	
	/**
	 * Detect which assets are already loaded by page markup.
	 * This is done by looking up all <asset> elements and registering the values of their src attributes.
	 */
	discoverLoadedAssets: function() {

		// wait until document has finished loading to avoid javascript errors
		if (!document.body) return; 

		var assets = this.findAssetUrlsInMarkup();
		for(var i=0;i<assets.length;i++)
			this.markAssetAsLoaded(assets[i]);
	},

	/**
	 * Extend url to a fully qualified url.
	 * @param string url 
	 */
	makeFullUrl: function(url) {

		// this is not intended to be a fully blown url "canonicalizator", 
		// just to handle the most common and basic asset paths used by Prado

		if (!this.baseUri) this.baseUri = window.location;

		if (url.indexOf('://')==-1)
		{
			var a = document.createElement('a');
			a.href = url;

			if (a.href.indexOf('://')!=-1)
				url = a.href;
			else
				{
					var path = a.pathname;
					if (path.substr(0,1)!='/') path = '/'+path;
					url = this.baseUri.protocol+'//'+this.baseUri.host+path;
				}
		}
		return url;
	},

	isAssetLoaded: function(url) {
		url = this.makeFullUrl(url);
		return (this.loadedAssets.indexOf(url)!=-1);
	},

	/**
	 * Mark asset as being already loaded
	 * @param string url of the asset 
	 */
	markAssetAsLoaded: function(url) {
		url = this.makeFullUrl(url);
		if (this.loadedAssets.indexOf(url)==-1)
			this.loadedAssets.push(url);
	},

	assetReadyStateChanged: function(url, element, callback, finalevent) {
		if (finalevent || (element.readyState == 'loaded') || (element.readyState == 'complete'))
		if (!element.assetCallbackFired)
		{
			element.assetCallbackFired = true;
			callback(url,element);
		}
	},

	assetLoadFailed: function(url, element, callback) {
		debugger;
		element.assetCallbackFired = true;
		if(typeof Logger != "undefined")
			Logger.error("Failed to load asset: "+url, this);
		if (!element.assetCallbackFired)
			callback(url,element,false);
	},

	/**
	 * Load a new asset dynamically into the page.
         * Please not thet loading is asynchronous and therefore you can't assume that
	 * the asset is loaded and ready when returning from this function.
	 * @param string url of the asset to load
	 * @param callback will be called when the asset has loaded (or failed to load)
	 */
	startAssetLoad: function(url, callback) {

		// create new <asset> element in page header
		var asset = this.createAssetElement(url);

		if (callback)
		{
			asset.onreadystatechange = this.assetReadyStateChanged.bind(this, url, asset, callback, false);
			asset.onload = this.assetReadyStateChanged.bind(this, url, asset, callback, true);
			asset.onerror = this.assetLoadFailed.bind(this, url, asset, callback);
			asset.assetCallbackFired = false;
		}

		var head = document.getElementsByTagName('head')[0];
   		head.appendChild(asset);

		// mark this asset as loaded
		this.markAssetAsLoaded(url);

		return (callback!=false);
	},

	/**
	 * Check whether a asset is loaded into the page, and if itsn't, load it now
	 * @param string url of the asset to check/load
	 * @return boolean returns true if asset is already loaded, or false, if loading has just started. callback will be called when loading has finished.
	 */
	ensureAssetIsLoaded: function(url, callback) {
		url = this.makeFullUrl(url);
		if (this.loadedAssets.indexOf(url)==-1)
			{
				this.startAssetLoad(url,callback);
				return false;
			}
		else
			return true;
	}

  }

};

  Prado.ScriptManagerClass = Class.extend(Prado.AssetManagerClass, {

	findAssetUrlsInMarkup: function() {
		var urls = new Array();
		var scripts = document.getElementsByTagName('script');
		for(var i=0;i<scripts.length;i++)
		{	
			var e = scripts[i]; var src = e.src;
			if (src!="")
				urls.push(src);
		}
		return urls;
	},

	createAssetElement: function(url) {
   		var asset = document.createElement('script');
   		asset.type = 'text/javascript';
   		asset.src = url;
//		asset.async = false; // HTML5 only
		return asset;
	}

  });

  Prado.StyleSheetManagerClass = Class.extend(Prado.AssetManagerClass, {

	findAssetUrlsInMarkup: function() {
		var urls = new Array();
		var scripts = document.getElementsByTagName('link');
		for(var i=0;i<scripts.length;i++)
		{	
			var e = scripts[i]; var href = e.href;
			if ((e.rel=="stylesheet") && (href.length>0))
				urls.push(href);
		}
		return urls;
	},

	createAssetElement: function(url) {
   		var asset = document.createElement('link');
   		asset.rel = 'stylesheet';
   		asset.media = 'screen';
   		asset.setAttribute('type', 'text/css');
   		asset.href = url;
//		asset.async = false; // HTML5 only
		return asset;
	}

  });

  if (typeof(Prado.ScriptManager)=="undefined") Prado.ScriptManager = new Prado.ScriptManagerClass();
  if (typeof(Prado.StyleSheetManager)=="undefined") Prado.StyleSheetManager = new Prado.StyleSheetManagerClass();

  // make sure we scan for loaded scripts again when the page has been loaded
  var discover = function() {
	Prado.ScriptManager.discoverLoadedAssets();
	Prado.StyleSheetManager.discoverLoadedAssets();
  }
  if (window.attachEvent) window.attachEvent('onload', discover);
  else if (window.addEventListener) window.addEventListener('load', discover, false);

