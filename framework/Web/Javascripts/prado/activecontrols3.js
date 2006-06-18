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
 * Active check box.
 */
Prado.WebUI.TActiveCheckBox = Class.extend(Prado.WebUI.CallbackControl,
{
	onPostBack : function(event, options)
	{
		new Prado.CallbackRequest(options.EventTarget, options);
	}		
});

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
		new Prado.CallbackRequest(options.EventTarget, options);
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
			new Prado.CallbackRequest(options.EventTarget, options);
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
		options = new Array(this.getToken(),"__TAutComplete_onSuggest__");
		Prado.Callback(this.options.EventTarget, options, null, this.options);
	},
	
  	onComplete : function(request, boundary) 
  	{
  		result = Prado.Element.extractContent(request.responseText, boundary);
  		if(typeof(result) == "string" && result.length > 0)
			this.updateChoices(result);
	}	
});

/** 
 * Callback Timer class. 
 */
Prado.WebUI.TCallbackTimer = Base.extend(
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
		Prado.WebUI.TCallbackTimer.register(this);
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
		new Prado.CallbackRequest(this.options.ID, this.options);
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



