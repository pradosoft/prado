/*! PRADO Active controls javascript file | github.com/pradosoft/prado */

/**
 * Generic postback control.
 */
Prado.WebUI.CallbackControl = jQuery.klass(Prado.WebUI.PostBackControl,
{
	onPostBack : function(options, event)
	{
		var request = new Prado.CallbackRequest(options.EventTarget, options);
		request.dispatch();
		event.preventDefault();
	}
});

/**
 * TActiveButton control.
 */
Prado.WebUI.TActiveButton = jQuery.klass(Prado.WebUI.CallbackControl);
/**
 * TActiveLinkButton control.
 */
Prado.WebUI.TActiveLinkButton = jQuery.klass(Prado.WebUI.CallbackControl);

Prado.WebUI.TActiveImageButton = jQuery.klass(Prado.WebUI.TImageButton,
{
	onPostBack : function(options, event)
	{
		this.addXYInput(options, event);
		var request = new Prado.CallbackRequest(options.EventTarget, options);
		request.dispatch();
		event.preventDefault();
		this.removeXYInput(options, event);
	}
});
/**
 * Active check box.
 */
Prado.WebUI.TActiveCheckBox = jQuery.klass(Prado.WebUI.CallbackControl,
{
	onPostBack : function(options, event)
	{
		var request = new Prado.CallbackRequest(options.EventTarget, options);
		if(request.dispatch()==false)
			event.preventDefault();
	}
});

/**
 * TActiveRadioButton control.
 */
Prado.WebUI.TActiveRadioButton = jQuery.klass(Prado.WebUI.TActiveCheckBox);


Prado.WebUI.TActiveCheckBoxList = jQuery.klass(Prado.WebUI.Control,
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
			new Prado.WebUI.TActiveCheckBox(checkBoxOptions);
		}
	}
});

Prado.WebUI.TActiveRadioButtonList = Prado.WebUI.TActiveCheckBoxList;

/**
 * TActiveTextBox control, handles onchange event.
 */
Prado.WebUI.TActiveTextBox = jQuery.klass(Prado.WebUI.TTextBox,
{
	onInit : function(options)
	{
		this.options=options;
		if(options['TextMode'] != 'MultiLine')
			this.observe(this.element, "keydown", this.handleReturnKey.bind(this));
		if(this.options['AutoPostBack']==true)
			this.observe(this.element, "change", jQuery.proxy(this.doCallback,this,options));
	},

	doCallback : function(options, event)
	{
		var request = new Prado.CallbackRequest(options.EventTarget, options);
		request.dispatch();
	    event.preventDefault();
	}
});

/**
 * TJuiAutoComplete control.
 */

Prado.WebUI.TJuiAutoComplete = jQuery.klass(Prado.WebUI.TActiveTextBox,
{
	initialize : function(options)
	{
		this.options = options;
		this.observers = new Array();
		this.hasResults = false;
		jQuery.extend(this.options, {
			source: this.getUpdatedChoices.bind(this),
			select: this.selectEntry.bind(this),
			focus: function () {
				return false;
			},
			minLength: this.options.minLength,
			frequency: this.options.frequency
		});
		jQuery('#'+options.ID).autocomplete(this.options)
		.data( "ui-autocomplete")._renderItem = function( ul, item ) {
			return jQuery( "<li>" )
			.attr( "data-value", item.value )
			.append( jQuery( "<div>" ).html( item.label ) )
			.appendTo( ul );
		};

		if(options.AutoPostBack)
			this.onInit(options);

		Prado.Registry[options.ID] = this;
	},

	doCallback : function(event, options)
	{
		if(!this.active)
		{
			var request = new Prado.CallbackRequest(this.options.EventTarget, options);
			request.dispatch();
			event.stopPropagation();
		}
	},

	getUpdatedChoices : function(request, callback)
	{
        var lastTerm = this.extractLastTerm(request.term);
		var params = new Array(lastTerm, "__TJuiAutoComplete_onSuggest__");
		var options = jQuery.extend(this.options, {
			'autocompleteCallback' : callback
		});
		Prado.Callback(this.options.EventTarget, params, this.onComplete.bind(this), this.options);
	},

	extractLastTerm: function(string)
	{
		var re = new RegExp("[" + (this.options.Separators || '') + "]");
		return string.split(re).pop().trim();
	},

	/**
	 * Overrides parent implements, don't update if no results.
	 */
	selectEntry: function(event, ui) {
		var value = event.target.value;
		var lastTerm = this.extractLastTerm(value);

		// strip (possibly) incomplete last part
		var previousTerms = value.substr(0, value.length - lastTerm.length);
		// and append selected value
		ui.item.value = previousTerms + ui.item.value;

		//ui.item.value = event.target.value;
		var options = [ui.item.id, "__TJuiAutoComplete_onSuggestionSelected__"];
		Prado.Callback(this.options.EventTarget, options, null, this.options);
	},


	onComplete : function(request, result)
  	{
  		var that = this;
  		if(that.options.textCssClass===undefined)
  		{
			jQuery.each(result, function(idx, item) {
				result[idx]['value']=jQuery.trim(jQuery('<div/>').html(item['label']).text());
			});
  		} else {
			jQuery.each(result, function(idx, item) {
				result[idx]['value']=jQuery.trim(jQuery('<div/>').html(item['label']).find('.'+that.options.textCssClass).text());
			});
  		}

		request.options.autocompleteCallback(result);
	}
});

/**
 * Time Triggered Callback class.
 */
Prado.WebUI.TTimeTriggeredCallback = jQuery.klass(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		this.options = jQuery.extend({ Interval : 1	}, options || {});
		Prado.WebUI.TTimeTriggeredCallback.registerTimer(this);
	},

	startTimer : function()
	{
		if(typeof(this.timer) == 'undefined' || this.timer == null)
			this.timer = this.setInterval(this.onTimerEvent.bind(this),this.options.Interval*1000);
	},

	stopTimer : function()
	{
		if(typeof(this.timer) != 'undefined')
		{
			this.clearInterval(this.timer);
			this.timer = null;
		}
	},

	resetTimer : function()
	{
		if(typeof(this.timer) != 'undefined')
		{
			this.clearInterval(this.timer);
			this.timer = null;
			this.timer = this.setInterval(this.onTimerEvent.bind(this),this.options.Interval*1000);
		}
	},

	onTimerEvent : function()
	{
		var request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.dispatch();
	},

	setTimerInterval : function(value)
	{
		if (this.options.Interval != value){
			this.options.Interval = value;
			this.resetTimer();
		}
	},

	onDone: function()
	{
		this.stopTimer();
	}
});

jQuery.extend(Prado.WebUI.TTimeTriggeredCallback,
{

	//class methods

	timers : {},

	registerTimer : function(timer)
	{
		Prado.WebUI.TTimeTriggeredCallback.timers[timer.options.ID] = timer;
	},

	start : function(id)
	{
		if(Prado.WebUI.TTimeTriggeredCallback.timers[id])
			Prado.WebUI.TTimeTriggeredCallback.timers[id].startTimer();
	},

	stop : function(id)
	{
		if(Prado.WebUI.TTimeTriggeredCallback.timers[id])
			Prado.WebUI.TTimeTriggeredCallback.timers[id].stopTimer();
	},

	setTimerInterval : function (id,value)
	{
		if(Prado.WebUI.TTimeTriggeredCallback.timers[id])
			Prado.WebUI.TTimeTriggeredCallback.timers[id].setTimerInterval(value);
	}
});

Prado.WebUI.ActiveListControl = jQuery.klass(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		if(this.element)
		{
			this.options = options;
			this.observe(this.element, "change", this.doCallback.bind(this));
		}
	},

	doCallback : function(event)
	{
		var request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.dispatch();
		event.preventDefault();
	}
});

Prado.WebUI.TActiveDropDownList = jQuery.klass(Prado.WebUI.ActiveListControl);
Prado.WebUI.TActiveListBox = jQuery.klass(Prado.WebUI.ActiveListControl);

/**
 * Observe event of a particular control to trigger a callback request.
 */
Prado.WebUI.TEventTriggeredCallback = jQuery.klass(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		this.options = options || {} ;
		var element = jQuery('#'+options['ControlID']).get(0);
		if(element)
			this.observe(element, this.getEventName(element), this.doCallback.bind(this));
	},

	getEventName : function(element)
	{
		var name = this.options.EventName;
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
		var request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.dispatch();
		if(this.options.StopEvent == true)
			event.preventDefault();
	}
});

/**
 * Observe changes to a property of a particular control to trigger a callback.
 */
Prado.WebUI.TValueTriggeredCallback = jQuery.klass(Prado.WebUI.Control,
{
	count : 1,

	observing : true,

	onInit : function(options)
	{
		this.options = options || {} ;
		this.options.PropertyName = this.options.PropertyName || 'value';
		var element = jQuery('#'+options['ControlID']).get(0);
		this.value = element ? element[this.options.PropertyName] : undefined;
		Prado.WebUI.TValueTriggeredCallback.register(this);
		this.startObserving();
	},

	stopObserving : function()
	{
		this.clearTimeout(this.timer);
		this.observing = false;
	},

	startObserving : function()
	{
		this.timer = this.setTimeout(this.checkChanges.bind(this), this.options.Interval*1000);
	},

	checkChanges : function()
	{
		var element = jQuery('#'+this.options.ControlID).get(0);
		if(element)
		{
			var value = element[this.options.PropertyName];
			if(this.value != value)
			{
				this.doCallback(this.value, value);
				this.value = value;
				this.count=1;
			}
			else
				this.count = this.count + this.options.Decay;
			if(this.observing)
				this.time = this.setTimeout(this.checkChanges.bind(this),
					parseInt(this.options.Interval*1000*this.count));
		}
	},

	doCallback : function(oldValue, newValue)
	{
		var request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		var param = {'OldValue' : oldValue, 'NewValue' : newValue};
		request.setCallbackParameter(param);
		request.dispatch();
	},

	onDone : function()
	{
		if (this.observing)
			this.stopObserving();
	}
});

jQuery.extend(Prado.WebUI.TValueTriggeredCallback,
{
	//class methods

	timers : {},

	register : function(timer)
	{
		Prado.WebUI.TValueTriggeredCallback.timers[timer.options.ID] = timer;
	},

	stop : function(id)
	{
		Prado.WebUI.TValueTriggeredCallback.timers[id].stopObserving();
	}
});

Prado.WebUI.TActiveTableCell = jQuery.klass(Prado.WebUI.CallbackControl);
Prado.WebUI.TActiveTableRow = jQuery.klass(Prado.WebUI.CallbackControl);
