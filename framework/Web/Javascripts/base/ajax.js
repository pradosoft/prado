/** 
 * Prado AJAX service. The default service provider is JPSpan.
 */
Prado.AJAX = { Service : 'Prototype' };

/**
 * Parse and execute javascript embedded in html.
 */
Prado.AJAX.EvalScript = function(output)
{

	var match = new RegExp(Ajax.Updater.ScriptFragment, 'img');
	var scripts  = output.match(match);
	if (scripts) 
	{
		match = new RegExp(Ajax.Updater.ScriptFragment, 'im');
		setTimeout((function() 
		{
			for (var i = 0; i < scripts.length; i++)
				eval(scripts[i].match(match)[1]);
		}).bind(this), 50);
	}
}


/**
 * AJAX service request using Prototype's AJAX request class.
 */
Prado.AJAX.Request = Class.create();
Prado.AJAX.Request.prototype = Object.extend(Ajax.Request.prototype, 
{
	/**
	 * Evaluate the respond JSON data, override parent implementing.
	 * If default eval fails, try parsing the JSON data (slower).
	 */
	evalJSON: function() 
	{
		try 
		{
			var json = this.transport.getResponseHeader('X-JSON'), object;
			object = eval(json);
			return object;
		} 
		catch (e) 
		{
			if(isString(json))
			{
				return Prado.AJAX.JSON.parse(json);
			}
		}
	},
	
	respondToReadyState: function(readyState) {
    var event = Ajax.Request.Events[readyState];
    var transport = this.transport, json = this.evalJSON();

	
	if(event == 'Complete' && transport.status)
    	Ajax.Responders.dispatch('on' + transport.status, this, transport, json);
    	
   (this.options['on' + event] || Prototype.emptyFunction)(transport, json);
    Ajax.Responders.dispatch('on' + event, this, transport, json);

	if (event == 'Complete')
      (this.options['on' + this.transport.status]
       || this.options['on' + (this.responseIsSuccess() ? 'Success' : 'Failure')]
       || Prototype.emptyFunction)(transport, json);

 
    /* Avoid memory leak in MSIE: clean up the oncomplete event handler */
    if (event == 'Complete')
      this.transport.onreadystatechange = Prototype.emptyFunction;
  }
  	
});

Prado.AJAX.Error = function(e, code) 
{
    e.name = 'Prado.AJAX.Error';
    e.code = code;
    return e;
}

/**
 * Post data builder, serialize the data using JSON.
 */
Prado.AJAX.RequestBuilder = Class.create();
Prado.AJAX.RequestBuilder.prototype = 
{
	initialize : function()
	{
		this.body = '';
		this.data = [];
	},
	encode : function(data)
	{
		return Prado.AJAX.JSON.stringify(data);
	},
	build : function(data) 
	{
		var sep = '';
        for ( var argName in data) 
		{
			if(isFunction(data[argName])) continue;
            try 
			{
                this.body += sep + argName + '=';
                this.body += encodeURIComponent(this.encode(data[argName]));
            } catch (e) {
                throw Prado.AJAX.Error(e, 1006);
            }
            sep = '&';
        }        
    },
	
	getAll : function()
	{
		this.build(this.data);
		return this.body;
	}
}


Prado.AJAX.RemoteObject = function(){};

/**
 * AJAX service request for Prado RemoteObjects
 */
Prado.AJAX.RemoteObject.Request = Class.create();
Prado.AJAX.RemoteObject.Request.prototype = Object.extend(Prado.AJAX.Request.prototype,
{
	/**
	 * Initialize the RemoteObject Request, overrides parent
	 * implementation by delaying the request to invokeRemoteObject.
	 */
	initialize : function(options)
	{
	    this.transport = Ajax.getTransport();
		this.setOptions(options);
		this.post = new Prado.AJAX.RequestBuilder();
	},

	/**
	 * Call the remote object, 
	 * @param string the remote server url
	 * @param array additional arguments
	 */
	invokeRemoteObject : function(url, args)
	{
		this.initParameters(args);
		this.options.postBody = this.post.getAll();
		this.request(url);
	},

	/**
	 * Set the additional arguments as post data with key '__parameters'
	 */
	initParameters : function(args)
	{
		this.post.data['__parameters'] = [];
		for(var i = 0; i<args.length; i++)
			this.post.data['__parameters'][i] = args[i];
	}
});

/**
 * Base proxy class for Prado RemoteObjects via AJAX.
 * e.g. 
 * <code>
 *	var TestObject1 = Class.create();
 *	TestObject1.prototype = Object.extend(new Prado.AJAX.RemoteObject(),
 *	{
 * 		initialize : function(handlers, options)
 *      {
 *           this.__serverurl = 'http://127.0.0.1/.....';
 *           this.baseInitialize(handlers, options);
 *	    }
 *
 *		method1 : function()
 *		{
 *			return this.__call(this.__serverurl, 'method1', arguments);
 *		}
 *	});
 *</code>
 * And client usage, 
 * <code>
 *	var test1 = new TestObject1(); //create new remote object
 *	test1.method1(); //call the method, no onComplete hook
 *
 *  var onComplete = { method1 : function(result){ alert(result) } };
 *  //create new remote object with onComplete callback
 *  var test2 = new TestObject1(onComplete); 
 *  test2.method1(); //call it, on success, onComplete's method1 is called.
 * </code>
 */
Prado.AJAX.RemoteObject.prototype = 
{
	baseInitialize : function(handlers, options)
	{
		this.__handlers = handlers || {};
		this.__service = new Prado.AJAX.RemoteObject.Request(options);
	},

	__call : function(url, method, args)
	{
		this.__service.options.onSuccess = this.__onSuccess.bind(this);
		this.__callback = method;
		return this.__service.invokeRemoteObject(url+"/"+method, args);
	},
	
	__onSuccess : function(transport, json)
	{
		if(this.__handlers[this.__callback])
			this.__handlers[this.__callback](json, transport.responseText);		
	}
};

/**
 * Respond to Prado AJAX request exceptions.
 */
Prado.AJAX.Exception =
{
	/**
	 * Server returns 505 exception. Just log it.
	 */
	"on505" : function(request, transport, e)
	{		
		var msg = 'HTTP '+transport.status+" with response";
		Logger.error(msg, transport.responseText);
		Logger.exception(e);
	},
	
	onComplete : function(request, transport, e)
	{
		if(transport.status != 505)
		{
			var msg = 'HTTP '+transport.status+" with response : \n";
			msg += transport.responseText + "\n";
			msg += "Data : \n"+inspect(e);
			Logger.warn(msg);
		}
	},

	format : function(e)
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

	logException : function(e)
	{
		var msg = Prado.AJAX.Exception.format(e);
		Logger.error("Server Error "+e.code, msg);
	}
}

//Add HTTP exception respones when logger is enabled.
Event.OnLoad(function()
{ 
	if(typeof Logger != "undefined") 
	{
		Logger.exception = Prado.AJAX.Exception.logException;
		Ajax.Responders.register(Prado.AJAX.Exception);
	}
});

/**
 * Prado Callback service that provides component intergration, 
 * viewstate (read only), and automatic form data serialization.
 * Usage: <code>new Prado.AJAX.Callback('MyPage.MyComponentID.raiseCallbackEvent', options)</code>
 * These classes should be called by the components developers.
 * For inline callback service, use <t>Prado.Callback(callbackID, params)</t>.
 */
Prado.AJAX.Callback = Class.create();
Prado.AJAX.Callback.prototype = Object.extend(new Prado.AJAX.RemoteObject(),
{
	
	/**
	 * Create and request a new Prado callback service.
	 * @param string|element the callback ID, must be of the form, <t>ClassName.ComponentID.MethodName</t>
	 * @param list options with list key onCallbackReturn, and more.
	 *
	 */
	initialize : function(ID, options)
	{
		if(!isString(ID) && typeof(ID.id) != "undefined")
			ID = ID.id;
		if(!isString(ID)) 
			throw new Error('A Control ID must be specified');
		this.baseInitialize(this, options);
		this.options = options || [];
		this.__service.post.data['__ID'] = ID;
		this.requestCallback();
	},
	
	/**
	 * Get form data for components that implements IPostBackHandler.
	 */
	collectPostData : function()
	{
		var IDs = Prado.AJAX.Callback.IDs;
		this.__service.post.data['__data'] = {};
		for(var i = 0; i<IDs.length; i++)
		{
			var id = IDs[i];
			if(id.indexOf("[]") > -1)
				this.__service.post.data['__data'][id] = 
					this.collectArrayPostData(id);
			else if(isObject($(id)))
				this.__service.post.data['__data'][id] = $F(id);
		}
	},

	collectArrayPostData : function(name)
	{
		var elements = document.getElementsByName(name);
		var data = [];
		$A(elements).each(function(el)
		{ 
			if($F(el)) data.push($F(el)); 
		});
		return data;
	},
	
	/**
	 * Prepares and calls the AJAX request.
	 * Collects the data from components that implements IPostBackHandler
	 * and the viewstate as part of the request payload.
	 */
	requestCallback : function()
	{
		this.collectPostData();
		if(Prado.AJAX.Validate(this.options))
			return this.__call(Prado.AJAX.Callback.Server, 'handleCallback', this.options.params);
	},

	/**
	 * On callback request return, call the onSuccess function.
	 */
	handleCallback : function(result, output)
	{
		if(typeof(result) != "undefined" && !isNull(result))
		{
			this.options.onSuccess(result['data'], output);
			if(result['actions'])
				result.actions.each(Prado.AJAX.Callback.Action.__run);
		}
	}
});

/**
 * Prase and evaluate Callback clien-side actions.
 */
Prado.AJAX.Callback.Action =
{
	__run : function(command)
	{
		for(var name in command)
		{
			//first parameter must be a valid element or begins with '@'
			if(command[name][0] && ($(command[name][0]) || command[name][0].indexOf("[]") > -1))
			{
				name.toFunction().apply(this,command[name]);
			}
		}
	}
};


/**
 * Returns false if validation required and validates to false, 
 * returns true otherwise.
 * @return boolean true if validation passes.
 */
Prado.AJAX.Validate = function(options)
{
	if(options.CausesValidation)
	{
		if(options.ValidatorGroup)
			return Prado.Validation.ValidateValidatorGroup(options.ValidatorGroup);
		else if(options.ValidationGroup)
			return Prado.Validation.ValidateValidationGroup(options.ValidationGroup);
		else
			return Prado.Validation.ValidateNonGroup(options.ValidationForm);
	}
	else
		return true;
};


//Available callback service
Prado.AJAX.Callback.Server = '';

//List of IDs that implements IPostBackHandler
Prado.AJAX.Callback.IDs = [];

/**
 * Simple AJAX callback interface, suitable for inline javascript.
 * e.g., <code><a href="..." onclick="Prado.Callback('..', 'Hello');">Click me</a></code>
 * @param string callback ID
 * @param array parameters to pass to the callback service
 */
Prado.Callback = function(ID, params, onSuccess, options)
{
	var callback =  
	{
		'params' : [params] || [],
		'onSuccess' : onSuccess || Prototype.emptyFunction, 
		'CausesValidation' : true
	};

	Object.extend(callback, options || {});
	
	new Prado.AJAX.Callback(ID, callback);
	return false;
}