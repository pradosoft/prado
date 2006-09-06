/**
 * Generic postback control.
 */
Prado.WebUI.CallbackControl = Class.extend(Prado.WebUI.PostBackControl,
{
	onPostBack : function(event, options)
	{
		request = new Prado.CallbackRequest(options.EventTarget, options);
		request.dispatch();
		Event.stop(event);
	}
});

/**
 * TActiveButton control.
 */
Prado.WebUI.TActiveButton = Class.extend(Prado.WebUI.CallbackControl);
/**
 * TActiveLinkButton control.
 */
Prado.WebUI.TActiveLinkButton = Class.extend(Prado.WebUI.CallbackControl);

Prado.WebUI.TActiveImageButton = Class.extend(Prado.WebUI.TImageButton,
{
	onPostBack : function(event, options)
	{
		this.addXYInput(event,options);
		request = new Prado.CallbackRequest(options.EventTarget, options);
		request.dispatch();
		Event.stop(event);
	}
});
/**
 * Active check box.
 */
Prado.WebUI.TActiveCheckBox = Class.extend(Prado.WebUI.CallbackControl,
{
	onPostBack : function(event, options)
	{
		request = new Prado.CallbackRequest(options.EventTarget, options);
		request.dispatch();
	}
});

/**
 * TActiveRadioButton control.
 */
Prado.WebUI.TActiveRadioButton = Class.extend(Prado.WebUI.TActiveCheckBox);


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
		request = new Prado.CallbackRequest(options.EventTarget, options);
		request.dispatch();
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
			request = new Prado.CallbackRequest(this.options.EventTarget, options);
			request.dispatch();
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
		options = new Array(this.getToken(),"__TAutoComplete_onSuggest__");
		Prado.Callback(this.options.EventTarget, options, null, this.options);
	},

  	onComplete : function(request, boundary)
  	{
  		result = Prado.Element.extractContent(request.transport.responseText, boundary);
  		if(typeof(result) == "string" && result.length > 0)
			this.updateChoices(result);
	}
});

/**
 * Time Triggered Callback class.
 */
Prado.WebUI.TTimeTriggeredCallback = Base.extend(
{
	count : 0,
	timeout : 0,

	constructor : function(options)
	{
		this.options = Object.extend(
		{
			Interval : 1,
			DecayRate : 0
		}, options || {})

		this.onComplete = this.options.onComplete;
		Prado.WebUI.TTimeTriggeredCallback.register(this);
	},

	startTimer : function()
	{
		this.options.onComplete = this.onRequestComplete.bind(this);
		setTimeout(this.onTimerEvent.bind(this), 200);
	},

	stopTimer : function()
	{
		(this.onComplete || Prototype.emptyFunction).apply(this, arguments);
		this.options.onComplete = undefined;
		clearTimeout(this.timer);
		this.timer = undefined;
		this.count = 0;
	},

	onTimerEvent : function()
	{
		this.options.params = this.timeout/1000;
		request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.dispatch();
	},

	onRequestComplete : function()
	{
		(this.onComplete || Prototype.emptyFunction).apply(this, arguments);
		this.timer = setTimeout(this.onTimerEvent.bind(this), this.getNewTimeout())
	},

	getNewTimeout : function()
	{
		switch(this.options.DecayType)
		{
			case 'Exponential':
				t = (Math.exp(this.options.DecayRate*this.count*this.options.Interval))-1;
			break;
			case 'Linear':
				t = this.options.DecayRate*this.count*this.options.Interval;
			break;
			case 'Quadratic':
				t = this.options.DecayRate*this.count*this.count*this.options.Interval;
			break;
			case 'Cubic':
				t = this.options.DecayRate*this.count*this.count*this.count*this.options.Interval;
			break;
			default : t = 0;
		}
		this.timeout = (t + this.options.Interval)*1000;
		this.count++;
		return parseInt(this.timeout);
	}
},
//class methods
{
	timers : {},

	register : function(timer)
	{
		this.timers[timer.options.ID] = timer;
	},

	start : function(id)
	{
		if(this.timers[id])
			this.timers[id].startTimer();
	},

	stop : function(id)
	{
		if(this.timers[id])
			this.timers[id].stopTimer();
	}
});

Prado.WebUI.ActiveListControl = Base.extend(
{
	constructor : function(options)
	{
		this.element = $(options.ID);
		this.options = options;
		Event.observe(this.element, "change", this.doCallback.bind(this));
	},

	doCallback : function(event)
	{
		request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.dispatch();
		Event.stop(event);
	}
});

Prado.WebUI.TActiveDropDownList = Prado.WebUI.ActiveListControl;
Prado.WebUI.TActiveListBox = Prado.WebUI.ActiveListControl;

/**
 * Observe event of a particular control to trigger a callback request.
 */
Prado.WebUI.TEventTriggeredCallback = Base.extend(
{
	constructor : function(options)
	{
		this.options = options;
		element = $(options['ControlID']);
		if(element)
			Event.observe(element, this.getEventName(element), this.doCallback.bind(this));
	},

	getEventName : function(element)
	{
		name = this.options.EventName;
   		if(typeof(name) == "undefined" && element.type)
		{
      		switch (element.type.toLowerCase())
			{
          		case 'password':
		        case 'text':
		        case 'textarea':
		        case 'select-one':
		        case 'select-multiple':
          			return 'change';
      		}
		}
		return typeof(name) == "undefined"  || name == "undefined" ? 'click' : name;
    },

	doCallback : function(event)
	{
		request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.dispatch();
		if(this.options.StopEvent == true)
			Event.stop(event);
	}
});

/**
 * Observe changes to a property of a particular control to trigger a callback.
 */
Prado.WebUI.TValueTriggeredCallback = Base.extend(
{
	count : 1,

	observing : true,

	constructor : function(options)
	{
		this.options = options;
		this.options.PropertyName = this.options.PropertyName || 'value';
		element = $(options['ControlID']);
		this.value = element ? element[this.options.PropertyName] : undefined;
		Prado.WebUI.TValueTriggeredCallback.register(this);
		this.startObserving();
	},

	stopObserving : function()
	{
		clearTimeout(this.timer);
		this.observing = false;
	},

	startObserving : function()
	{
		this.timer = setTimeout(this.checkChanges.bind(this), this.options.Interval*1000);
	},

	checkChanges : function()
	{
		element = $(this.options.ControlID);
		if(element)
		{
			value = element[this.options.PropertyName];
			if(this.value != value)
			{
				this.doCallback(this.value, value);
				this.value = value;
				this.count=1;
			}
			else
				this.count = this.count + this.options.Decay;
			if(this.observing)
				this.time = setTimeout(this.checkChanges.bind(this),
					parseInt(this.options.Interval*1000*this.count));
		}
	},

	doCallback : function(oldValue, newValue)
	{
		request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		param = {'OldValue' : oldValue, 'NewValue' : newValue};
		request.setParameter(param);
		request.dispatch();
	}
},
//class methods
{
	timers : {},

	register : function(timer)
	{
		this.timers[timer.options.ID] = timer;
	},

	stop : function(id)
	{
		if(this.timers[id])
			this.timers[id].stopObserving();
	}
});
