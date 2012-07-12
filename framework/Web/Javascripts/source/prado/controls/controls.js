Prado.WebUI = Class.create();

Prado.WebUI.Control = Class.create({

	initialize : function(options)
	{
	    this.registered = false;
		this.ID = options.ID;
		this.element = $(this.ID);
		this.observers = new Array();
		this.intervals = new Array();
		var e;
		if (e = Prado.Registry.get(this.ID))
			this.replace(e, options);
		else
			this.register(options);

		if (this === Prado.Registry.get(this.ID))
		{
			this.registered = true;
			if(this.onInit)
				this.onInit(options);
		}
	},

	/**
	 * Registers the control wrapper in the Prado client side control registry
	 * @param array control wrapper options
	 */
	register : function(options)
	{
		return Prado.Registry.set(options.ID, this);
	},

	/**
	 * De-registers the control wrapper in the Prado client side control registry
	 */
	deregister : function()
	{
		// extra check so we don't ever deregister another wrapper
		if (Prado.Registry.get(this.ID)===this)
			return Prado.Registry.unset(this.ID);
		else
			debugger; // invoke debugger - this should never happen
	},

	/**
	 * Replaces and control wrapper for an already existing control in the Prado client side control registry
	 * @param object reference to the old wrapper
	 * @param array control wrapper options
	 */
	replace : function(oldwrapper, options)
	{
		// if there's some advanced state management in the wrapper going on, then
		// this method could be used either to copy the current state of the control
		// from the old wrapper to this new one (which then could live on, while the old
		// one could get destroyed), or to copy the new, changed options to the old wrapper,
		// (which could then left intact to keep working, while this new wrapper could be
		// disposed of by exiting its initialization without installing any handlers or 
		// leaving any references to it)
		//

		// for now this method is simply deinitializing and deregistering the old wrapper, 
		// and then registering the new wrapper for the control id

		if (oldwrapper.deinitialize)
			oldwrapper.deinitialize();

		return this.register(options);
	},

	/**
	 * Registers an event observer which will be automatically disposed of when the wrapper 
	 * is deregistered
	 * @param element DOM element reference or id to attach the event handler to
	 * @param string event name to observe
         * @param handler event handler function
	 */
	observe: function(element, eventName, handler)
	{
		var e = { _element: element, _eventName: eventName, _handler: handler };
		this.observers.push(e);
		return Event.observe(e._element,e._eventName,e._handler);
	},

	/**
	 * Checks whether an event observer is installed and returns its index
	 * @param element DOM element reference or id the event handler was attached to
	 * @param string event name observed
         * @param handler event handler function
	 * @result int false if the event handler is not installed, or 1-based index when installed
	 */
	findObserver: function(element, eventName, handler)
	{
		var e = { _element: element, _eventName: eventName, _handler: handler };
		var idx = -1;
		for(var i=0;i<this.observers.length;i++)
		{	
			var o = this.observers[i];
			if ((o._element===element) && (o._eventName===eventName) && (o._handler===handler))
			{
				idx = i;
				break;
			}
		}
		return idx;
	},
	

	/**
	 * Degisters an event observer from the list of automatically disposed handlers
	 * @param element DOM element reference or id the event handler was attached to
	 * @param string event name observed
         * @param handler event handler function
	 */
	stopObserving: function(element, eventName, handler)
	{
		var idx = this.findObserver(element,eventName,handler);
		if (idx!=-1)
			this.observers = this.observers.without(this.observers[idx]);
		else
			debugger; // shouldn't happen

		return Event.stopObserving(element,eventName,handler);
	},

	/**
	 * Registers a code snippet or function to be executed after a delay, if the
	 * wrapper hasn't been destroyed in the meantime
	 * @param code function or code snippet to execute
	 * @param int number of milliseconds to wait before executing
	 * @return int unique ID that can be used to cancel the scheduled execution 
	 */
	setTimeout: function(func, delay)
	{
		if (!Object.isFunction(func)) 
		{
			var expr = func;
			func = function() { return eval(expr); }
		};
		var obj = this;
		return window.setTimeout(function() {
			if (!obj.isLingering())
				func();
			obj = null;
		},delay);
	},

	/**
	 * Cancels a previously scheduled code snippet or function
	 * @param int unique ID returned by setTimeout()
	 */
	clearTimeout: function(timeoutid)
	{
		return window.clearTimeout(timeoutid);
	},

	/**
	 * Registers a code snippet or function to be executed periodically, up until the
	 * wrapper gets destroyed or the schedule cancelled using cancelInterval()
	 * @param code function or code snippet to execute
	 * @param int number of milliseconds to wait before executing
	 * @return int unique ID that can be used to cancel the interval (see clearInterval() method)
	 */
	setInterval: function(func, delay)
	{
		if (!Object.isFunction(func)) func = function() { eval(func); };
		var obj = this;
		var h = window.setInterval(function() {
			if (!obj.isLingering())
				func();
		},delay);
		this.intervals.push(h);
		return h;
	},

	/**
	 * Deregisters a snipper or function previously registered with setInterval()
	 * @param int unique ID of interval (returned by setInterval() previously)
	 */
	clearInterval: function(intervalid)
	{
		window.clearInterval(intervalid);
		this.intervals = this.intervals.without(intervalid);
	},

	/**
	 * Tells whether this is a wrapper that has already been deregistered and is lingering
	 * @return bool true if object
	 */
	isLingering: function()
	{
		return !this.registered;
	},

	/**
	 * Deinitializes the control wrapper by calling the onDone method and the deregistering it
	 * @param array control wrapper options
	 */
	deinitialize : function()
	{
		if (this.registered)
			{
				if(this.onDone)
					this.onDone();

				// automatically stop all intervals
				while (this.intervals.length>0)	
					window.clearInterval(this.intervals.pop());

				// automatically deregister all installed observers
				while (this.observers.length>0)	
				{
					var e = this.observers.pop();
					Event.stopObserving(e._element,e._eventName,e._handler);
				}
			}
		else
			debugger; // shouldn't happen

		this.deregister();

		this.registered = false;
	}

});

Prado.WebUI.PostBackControl = Class.create(Prado.WebUI.Control, {

	onInit : function(options)
	{
		this._elementOnClick = null;

		if (!this.element) 
			debugger; // element not found
		else
			{
				//capture the element's onclick function
				if(typeof(this.element.onclick)=="function")
				{
					this._elementOnClick = this.element.onclick.bind(this.element);
					this.element.onclick = null;
				}
				this.observe(this.element, "click", this.elementClicked.bindEvent(this,options));
			}
	},

	elementClicked : function(event, options)
	{
		var src = Event.element(event);
		var doPostBack = true;
		var onclicked = null;

		if(this._elementOnClick)
		{
			var onclicked = this._elementOnClick(event);
			if(typeof(onclicked) == "boolean")
				doPostBack = onclicked;
		}
		if(doPostBack && !Prado.Element.isDisabled(src))
			this.onPostBack(event,options);
		if(typeof(onclicked) == "boolean" && !onclicked)
			Event.stop(event);
	},

	onPostBack : function(event, options)
	{
		Prado.PostBack(event,options);
	}

});

Prado.WebUI.TButton = Class.create(Prado.WebUI.PostBackControl);
Prado.WebUI.TLinkButton = Class.create(Prado.WebUI.PostBackControl);
Prado.WebUI.TCheckBox = Class.create(Prado.WebUI.PostBackControl);
Prado.WebUI.TBulletedList = Class.create(Prado.WebUI.PostBackControl);
Prado.WebUI.TImageMap = Class.create(Prado.WebUI.PostBackControl);

/**
 * TImageButton client-side behaviour. With validation, Firefox needs
 * to capture the x,y point of the clicked image in hidden form fields.
 */
Prado.WebUI.TImageButton = Class.create(Prado.WebUI.PostBackControl, 
{
	/**
	 * Override parent onPostBack function, tried to add hidden forms
	 * inputs to capture x,y clicked point.
	 */
	onPostBack : function(event, options)
	{
		this.addXYInput(event,options);
		Prado.PostBack(event, options);
		this.removeXYInput(event,options);
	},

	/**
	 * Add hidden inputs to capture the x,y point clicked on the image.
	 * @param event DOM click event.
	 * @param array image button options.
	 */
	addXYInput : function(event,options)
	{
		var imagePos = this.element.cumulativeOffset();
		var clickedPos = [event.clientX, event.clientY];
		var x = clickedPos[0]-imagePos[0]+1;
		var y = clickedPos[1]-imagePos[1]+1;
		x = x < 0 ? 0 : x;
		y = y < 0 ? 0 : y;
		var id = options['EventTarget'];
		var x_input = $(id+"_x");
		var y_input = $(id+"_y");
		if(x_input)
		{
			x_input.value = x;
		}
		else
		{
			x_input = INPUT({type:'hidden',name:id+'_x','id':id+'_x',value:x});
			this.element.parentNode.appendChild(x_input);
		}
		if(y_input)
		{
			y_input.value = y;
		}
		else
		{
			y_input = INPUT({type:'hidden',name:id+'_y','id':id+'_y',value:y});
			this.element.parentNode.appendChild(y_input);
		}
	},

	/**
	 * Remove hidden inputs for x,y-click capturing
	 * @param event DOM click event.
	 * @param array image button options.
	 */
	removeXYInput : function(event,options)
	{
		var id = options['EventTarget'];
		this.element.parentNode.removeChild($(id+"_x"));
		this.element.parentNode.removeChild($(id+"_y"));
	}
});


/**
 * Radio button, only initialize if not already checked.
 */
Prado.WebUI.TRadioButton = Class.create(Prado.WebUI.PostBackControl,
{
	initialize : function($super, options)
	{
		this.element = $(options['ID']);
		if(this.element)
		{
			if(!this.element.checked)
				$super(options);
		}
	}
});


Prado.WebUI.TTextBox = Class.create(Prado.WebUI.PostBackControl,
{
	onInit : function(options)
	{
		this.options=options;
		if(this.options['TextMode'] != 'MultiLine')
			this.observe(this.element, "keydown", this.handleReturnKey.bind(this));
		if(this.options['AutoPostBack']==true)
			this.observe(this.element, "change", Prado.PostBack.bindEvent(this,options));
	},

	handleReturnKey : function(e)
	{
		 if(Event.keyCode(e) == Event.KEY_RETURN)
        {
			var target = Event.element(e);
			if(target)
			{
				if(this.options['AutoPostBack']==true)
				{
					Event.fireEvent(target, "change");
					Event.stop(e);
				}
				else
				{
					if(this.options['CausesValidation'] && typeof(Prado.Validation) != "undefined")
					{
						if(!Prado.Validation.validate(this.options['FormID'], this.options['ValidationGroup'], $(this.options['ID'])))
							return Event.stop(e);
					}
				}
			}
		}
	}
});

Prado.WebUI.TListControl = Class.create(Prado.WebUI.PostBackControl,
{
	onInit : function(options)
	{
		this.observe(this.element, "change", Prado.PostBack.bindEvent(this,options));
	}
});

Prado.WebUI.TListBox = Class.create(Prado.WebUI.TListControl);
Prado.WebUI.TDropDownList = Class.create(Prado.WebUI.TListControl);

Prado.WebUI.DefaultButton = Class.create(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		this.options = options;
		this.observe(options['Panel'], 'keydown', this.triggerEvent.bindEvent(this));
	},

	triggerEvent : function(ev, target)
	{
		var enterPressed = Event.keyCode(ev) == Event.KEY_RETURN;
		var isTextArea = Event.element(ev).tagName.toLowerCase() == "textarea";
		var isValidButton = Event.element(ev).tagName.toLowerCase() == "input" &&  Event.element(ev).type.toLowerCase() == "submit";
		
		if(enterPressed && !isTextArea && !isValidButton)
		{
			var defaultButton = $(this.options['Target']);
			if(defaultButton)
			{
				this.triggered = true;
				Event.fireEvent(defaultButton, this.options['Event']);
				Event.stop(ev);
			}
		}
	}
});

Prado.WebUI.TTextHighlighter = Class.create();
Prado.WebUI.TTextHighlighter.prototype =
{
	initialize:function(id)
	{
		if(!window.clipboardData) return;
		var options =
		{
			href : 'javascript:;/'+'/copy code to clipboard',
			onclick : 'Prado.WebUI.TTextHighlighter.copy(this)',
			onmouseover : 'Prado.WebUI.TTextHighlighter.hover(this)',
			onmouseout : 'Prado.WebUI.TTextHighlighter.out(this)'
		}
		var div = DIV({className:'copycode'}, A(options, 'Copy Code'));
		document.write(DIV(null,div).innerHTML);
	}
};

Object.extend(Prado.WebUI.TTextHighlighter,
{
	copy : function(obj)
	{
		var parent = obj.parentNode.parentNode.parentNode;
		var text = '';
		for(var i = 0; i < parent.childNodes.length; i++)
		{
			var node = parent.childNodes[i];
			if(node.innerText)
				text += node.innerText == 'Copy Code' ? '' : node.innerText;
			else
				text += node.nodeValue;
		}
		if(text.length > 0)
			window.clipboardData.setData("Text", text);
	},

	hover : function(obj)
	{
		obj.parentNode.className = "copycode copycode_hover";
	},

	out : function(obj)
	{
		obj.parentNode.className = "copycode";
	}
});


Prado.WebUI.TCheckBoxList = Base.extend(
{
	constructor : function(options)
	{
		Prado.Registry.set(options.ListID, this);
		for(var i = 0; i<options.ItemCount; i++)
		{
			var checkBoxOptions = Object.extend(
			{
				ID : options.ListID+"_c"+i,
				EventTarget : options.ListName+"$c"+i
			}, options);
			new Prado.WebUI.TCheckBox(checkBoxOptions);
		}
	}
});

Prado.WebUI.TRadioButtonList = Base.extend(
{
	constructor : function(options)
	{
		Prado.Registry.set(options.ListID, this);
		for(var i = 0; i<options.ItemCount; i++)
		{
			var radioButtonOptions = Object.extend(
			{
				ID : options.ListID+"_c"+i,
				EventTarget : options.ListName+"$c"+i
			}, options);
			new Prado.WebUI.TRadioButton(radioButtonOptions);
		}
	}
});
