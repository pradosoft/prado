/*! PRADO controls javascript file | github.com/pradosoft/prado */

Prado.WebUI = jQuery.klass();

Prado.WebUI.Control = jQuery.klass({

	initialize : function(options)
	{
	    this.registered = false;
		this.ID = options.ID;
		this.element = jQuery("#" + this.ID).get(0);
		this.observers = new Array();
		this.intervals = new Array();

		if (typeof Prado.Registry[this.ID] == 'undefined') {
			this.register(options);
		} else {
			this.replace(Prado.Registry[this.ID], options);
		}

		if (this === Prado.Registry[this.ID])
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
		return Prado.Registry[options.ID] = this;
	},

	/**
	 * De-registers the control wrapper in the Prado client side control registry
	 */
	deregister : function()
	{
		// extra check so we don't ever deregister another wrapper
		var value = Prado.Registry[this.ID];
		if (value===this)
		{
			delete Prado.Registry[this.ID];
			return value;
		}
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
		{
			oldwrapper.deinitialize();
		}

		return this.register(options);
	},

	/**
	 * Registers an event observer which will be automatically disposed of when the wrapper
	 * is deregistered
	 * @param element DOM element reference or id to attach the event handler to
	 * @param string event name to observe
         * @param handler event handler function
	 */
	observe: function(element, eventName, handler, options)
	{
		var e = { _element: element, _eventName: eventName, _handler: handler };
		this.observers.push(e);
		return jQuery(e._element).bind(e._eventName, options, e._handler);
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
			this.observers.splice(idx, 1);
		else
			debugger; // shouldn't happen

		return jQuery(element).unbind(eventName, handler);
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
		if (!jQuery.isFunction(func))
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
		if (!jQuery.isFunction(func)) func = function() { eval(func); };
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
		this.intervals.splice( jQuery.inArray(intervalid, this.intervals), 1 );
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
					jQuery(e._element).unbind(e._eventName, e._handler);
				}
			}
		else
			debugger; // shouldn't happen

		this.deregister();

		this.registered = false;
	}

});

Prado.WebUI.PostBackControl = jQuery.klass(Prado.WebUI.Control, {

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
				this.observe(this.element, "click", jQuery.proxy(this.elementClicked,this,options));
			}
	},

	elementClicked : function(options, event)
	{
		var src = event.target;
		var doPostBack = true;
		var onclicked = null;

		if(this._elementOnClick)
		{
			var onclicked = this._elementOnClick(event);
			if(typeof(onclicked) == "boolean")
				doPostBack = onclicked;
		}
		if(doPostBack && !jQuery(src).is(':disabled'))
			this.onPostBack(options,event);
		if(typeof(onclicked) == "boolean" && !onclicked)
		{
			event.stopPropagation();
			event.preventDefault();
			return false;
		}
	},

	onPostBack : function(options, event)
	{
		new Prado.PostBack(options, event);
	}

});

Prado.WebUI.TButton = jQuery.klass(Prado.WebUI.PostBackControl);
Prado.WebUI.TLinkButton = jQuery.klass(Prado.WebUI.PostBackControl);
Prado.WebUI.TCheckBox = jQuery.klass(Prado.WebUI.PostBackControl);
Prado.WebUI.TBulletedList = jQuery.klass(Prado.WebUI.PostBackControl);
Prado.WebUI.TImageMap = jQuery.klass(Prado.WebUI.PostBackControl);

/**
 * TImageButton client-side behaviour. With validation, Firefox needs
 * to capture the x,y point of the clicked image in hidden form fields.
 */
Prado.WebUI.TImageButton = jQuery.klass(Prado.WebUI.PostBackControl,
{
	/**
	 * Override parent onPostBack function, tried to add hidden forms
	 * inputs to capture x,y clicked point.
	 */
	onPostBack : function(options, event)
	{
		this.addXYInput(options, event);
		new Prado.PostBack(options, event);
		this.removeXYInput(options, event);
	},

	/**
	 * Add hidden inputs to capture the x,y point clicked on the image.
	 * @param event DOM click event.
	 * @param array image button options.
	 */
	addXYInput : function(options, event)
	{
		var imagePos = jQuery(this.element).offset();
		var clickedPos = [event.clientX, event.clientY];
		var x = clickedPos[0]-imagePos['left']+1;
		var y = clickedPos[1]-imagePos['top']+1;
		x = x < 0 ? 0 : x;
		y = y < 0 ? 0 : y;
		var id = this.element.id;
		var name = options['EventTarget'];
		var form = this.element.form || jQuery('#PRADO_PAGESTATE').get(0).form;

		var input=null;
		input = document.createElement("input");
		input.setAttribute("type", "hidden");
		input.setAttribute("id", id+"_x");
		input.setAttribute("name", name+"_x");
		input.setAttribute("value", x);
		form.appendChild(input);

		input = document.createElement("input");
		input.setAttribute("type", "hidden");
		input.setAttribute("id", id+"_y");
		input.setAttribute("name", name+"_y");
		input.setAttribute("value", y);
		form.appendChild(input);
	},

	/**
	 * Remove hidden inputs for x,y-click capturing
	 * @param event DOM click event.
	 * @param array image button options.
	 */
	removeXYInput : function(options, event)
	{
		var id = this.element.id;
		jQuery('#'+id+'_x').remove();
		jQuery('#'+id+'_y').remove();
	}
});


/**
 * Radio button, only initialize if not already checked.
 */
Prado.WebUI.TRadioButton = jQuery.klass(Prado.WebUI.PostBackControl,
{
	initialize : function($super, options)
	{
		this.element = jQuery("#" + options['ID']).get(0);
		if(this.element)
		{
			if(!this.element.checked)
				$super(options);
		}
	}
});


Prado.WebUI.TTextBox = jQuery.klass(Prado.WebUI.PostBackControl,
{
	onInit : function(options)
	{
		this.options=options;
		if(this.options['TextMode'] != 'MultiLine')
			this.observe(this.element, "keydown", this.handleReturnKey.bind(this));
		if(this.options['AutoPostBack']==true)
			this.observe(this.element, "change", jQuery.proxy(this.doPostback,this,options));
	},

	doPostback : function(options, event)
	{
		new Prado.PostBack(options, event);
	},

	handleReturnKey : function(e)
	{
		 if(e.keyCode == 13) // KEY_RETURN
        {
			var target = e.target;
			if(target)
			{
				if(this.options['AutoPostBack']==true)
				{
					jQuery(target).trigger( "change" );
					e.stopPropagation();
				}
				else
				{
					if(this.options['CausesValidation'] && typeof(Prado.Validation) != "undefined")
					{
						if(!Prado.Validation.validate(this.options['FormID'], this.options['ValidationGroup'], jQuery(this.options['ID'])))
							return e.stopPropagation();
					}
				}
			}
		}
	}
});

Prado.WebUI.TListControl = jQuery.klass(Prado.WebUI.PostBackControl,
{
	onInit : function(options)
	{
			this.observe(this.element, "change", jQuery.proxy(this.doPostback,this,options));
	},

	doPostback : function(options, event)
	{
		new Prado.PostBack(options, event);
	}
});

Prado.WebUI.TListBox = jQuery.klass(Prado.WebUI.TListControl);
Prado.WebUI.TDropDownList = jQuery.klass(Prado.WebUI.TListControl);

Prado.WebUI.DefaultButton = jQuery.klass(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		this.options = options;
		this.observe(jQuery('#'+options['Panel']), "keydown", jQuery.proxy(this.triggerEvent,this));
	},

	triggerEvent : function(ev)
	{
		var enterPressed = ev.keyCode == 13;
		var isTextArea = ev.target.tagName.toLowerCase() == "textarea";
		var isHyperLink = ev.target.tagName.toLowerCase() == "a" && ev.target.hasAttribute("href");
		var isValidButton = ev.target.tagName.toLowerCase() == "input" &&  ev.target.type.toLowerCase() == "submit";

		if(enterPressed && !isTextArea && !isValidButton && !isHyperLink)
		{
			var defaultButton = jQuery('#'+this.options['Target']);
			if(defaultButton)
			{
				this.triggered = true;
				defaultButton.trigger(this.options['Event']);
				ev.preventDefault();
			}
		}
	}
});

Prado.WebUI.TTextHighlighter = jQuery.klass(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		this.options = options;

		var code = jQuery('#'+this.options.ID+'_code');
		var btn;

		if(this.options.copycode)
		{
			btn = jQuery('<input type="button">')
				.addClass("copycode")
				.val('Copy code')
				.attr({
					'id': '#'+this.options.ID+'_copy',
					'data-clipboard-text': code.text()
				})
				.css({'float': 'right'});

			var clipboard = new Clipboard(jQuery(btn).get(0));
		}

		hljs.configure({
			tabReplace: options.tabsize || '    '
		})
		hljs.highlightBlock(code.get(0));

		if(this.options.linenum) {
			hljs.lineNumbersBlock(code.get(0));
		}

		if(this.options.copycode)
		{
			btn.prependTo('#'+this.options.ID+'_code');
		}
	}
});


Prado.WebUI.TCheckBoxList = jQuery.klass(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		for(var i = 0; i<options.ItemCount; i++)
		{
			var checkBoxOptions = jQuery.extend({}, options,
			{
				ID : options.ID+"_c"+i,
				EventTarget : options.ListName+"$c"+i
			});
			new Prado.WebUI.TCheckBox(checkBoxOptions);
		}
	}
});

Prado.WebUI.TRadioButtonList = jQuery.klass(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		for(var i = 0; i<options.ItemCount; i++)
		{
			var radioButtonOptions = jQuery.extend({}, options,
			{
				ID : options.ID+"_c"+i,
				EventTarget : options.ListName+"$c"+i
			});
			new Prado.WebUI.TRadioButton(radioButtonOptions);
		}
	}
});
