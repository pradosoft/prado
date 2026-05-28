/*! PRADO Active controls javascript file | github.com/pradosoft/prado */

/**
 * Generic postback control.
 */
Prado.WebUI.CallbackControl = Prado.Class(Prado.WebUI.PostBackControl,
{
	onPostBack(options, event) {
		const request = new Prado.CallbackRequest(options.EventTarget, options);
		request.dispatch();
		event.preventDefault();
	}
});

/**
 * TActiveButton control.
 */
Prado.WebUI.TActiveButton = Prado.Class(Prado.WebUI.CallbackControl);
/**
 * TActiveLinkButton control.
 */
Prado.WebUI.TActiveLinkButton = Prado.Class(Prado.WebUI.CallbackControl);

Prado.WebUI.TActiveImageButton = Prado.Class(Prado.WebUI.TImageButton,
{
	onPostBack(options, event) {
		this.addXYInput(options, event);
		const request = new Prado.CallbackRequest(options.EventTarget, options);
		request.dispatch();
		event.preventDefault();
		this.removeXYInput(options, event);
	}
});
/**
 * Active check box.
 */
Prado.WebUI.TActiveCheckBox = Prado.Class(Prado.WebUI.CallbackControl,
{
	onPostBack(options, event) {
		const request = new Prado.CallbackRequest(options.EventTarget, options);
		if(request.dispatch()==false)
			event.preventDefault();
	}
});

/**
 * TActiveRadioButton control.
 */
Prado.WebUI.TActiveRadioButton = Prado.Class(Prado.WebUI.TActiveCheckBox);


Prado.WebUI.TActiveCheckBoxList = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		for(let i = 0; i<options.ItemCount; i++)
		{
			const checkBoxOptions = {
				...options,
				ID : `${options.ID}_c${i}`,
				EventTarget : `${options.ListName}$c${i}`
			};
			new Prado.WebUI.TActiveCheckBox(checkBoxOptions);
		}
	}
});

Prado.WebUI.TActiveRadioButtonList = Prado.WebUI.TActiveCheckBoxList;

/**
 * TActiveTextBox control, handles onchange event.
 */
Prado.WebUI.TActiveTextBox = Prado.Class(Prado.WebUI.TTextBox,
{
	onInit(options) {
		this.options=options;
		if(options['TextMode'] != 'MultiLine')
			this.observe(this.element, "keydown", this.handleReturnKey.bind(this));
		if(this.options['AutoPostBack']==true)
			this.observe(this.element, "change", this.doCallback.bind(this, options));
	},

	doCallback(options, event) {
		const request = new Prado.CallbackRequest(options.EventTarget, options);
		request.dispatch();
	    event.preventDefault();
	}
});

/**
 * TJuiAutoComplete control.
 */

Prado.WebUI.TJuiAutoComplete = Prado.Class(Prado.WebUI.TActiveTextBox,
{
	initialize(options) {
		this.options = options;
		this.observers = new Array();
		this.hasResults = false;
		Object.assign(this.options, {
			source: this.getUpdatedChoices.bind(this),
			select: this.selectEntry.bind(this),
			focus() {
				return false;
			},
			minLength: this.options.minLength,
			frequency: this.options.frequency
		});
		jQuery(`#${options.ID}`).autocomplete(this.options)
		.data( "ui-autocomplete")._renderItem = (ul, item) => jQuery( "<li>" )
        .attr( "data-value", item.value )
        .append( jQuery( "<div>" ).html( item.label ) )
        .appendTo( ul );

		if(options.AutoPostBack)
			this.onInit(options);

		Prado.Registry[options.ID] = this;
	},

	doCallback(event, options) {
		if(!this.active)
		{
			const request = new Prado.CallbackRequest(this.options.EventTarget, options);
			request.dispatch();
			event.stopPropagation();
		}
	},

	getUpdatedChoices(request, callback) {
        const lastTerm = this.extractLastTerm(request.term);
		const params = new Array(lastTerm, "__TJuiAutoComplete_onSuggest__");
		const options = Object.assign(this.options, {
			'autocompleteCallback' : callback
		});
		Prado.Callback(this.options.EventTarget, params, this.onComplete.bind(this), this.options);
	},

	extractLastTerm(string) {
		const re = new RegExp(`[${this.options.Separators || ''}]`);
		return string.split(re).pop().trim();
	},

	/**
	 * Overrides parent implements, don't update if no results.
	 */
	selectEntry(event, ui) {
		const value = event.target.value;
		const lastTerm = this.extractLastTerm(value);

		// strip (possibly) incomplete last part
		const previousTerms = value.substr(0, value.length - lastTerm.length);
		// and append selected value
		ui.item.value = previousTerms + ui.item.value;

		//ui.item.value = event.target.value;
		const options = [ui.item.id, "__TJuiAutoComplete_onSuggestionSelected__"];
		Prado.Callback(this.options.EventTarget, options, null, this.options);
	},


	onComplete(request, result) {
		// Decode the HTML in `label` to plain text for the underlying value.
		// Uses a detached <div> as the HTML parser; matches the old
		// jQuery('<div/>').html(label).text() pattern.
		const decode = (html) => {
			const div = document.createElement('div');
			div.innerHTML = html;
			return div;
		};
		if(this.options.textCssClass === undefined) {
			for (const item of result) {
				item.value = decode(item.label).textContent.trim();
			}
		} else {
			const sel = `.${this.options.textCssClass}`;
			for (const item of result) {
				const node = decode(item.label).querySelector(sel);
				item.value = (node ? node.textContent : '').trim();
			}
		}

		request.options.autocompleteCallback(result);
	}
});

/**
 * Time Triggered Callback class.
 */
Prado.WebUI.TTimeTriggeredCallback = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.options = { Interval : 1, ...(options || {}) };
		Prado.WebUI.TTimeTriggeredCallback.registerTimer(this);
	},

	startTimer() {
		if(typeof(this.timer) == 'undefined' || this.timer == null)
			this.timer = this.setInterval(this.onTimerEvent.bind(this),this.options.Interval*1000);
	},

	stopTimer() {
		if(typeof(this.timer) != 'undefined')
		{
			this.clearInterval(this.timer);
			this.timer = null;
		}
	},

	resetTimer() {
		if(typeof(this.timer) != 'undefined')
		{
			this.clearInterval(this.timer);
			this.timer = null;
			this.timer = this.setInterval(this.onTimerEvent.bind(this),this.options.Interval*1000);
		}
	},

	onTimerEvent() {
		const request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.dispatch();
	},

	setTimerInterval(value) {
		if (this.options.Interval != value){
			this.options.Interval = value;
			this.resetTimer();
		}
	},

	onDone() {
		this.stopTimer();
	}
});

Object.assign(Prado.WebUI.TTimeTriggeredCallback,
{

	//class methods

	timers : {},

	registerTimer(timer) {
		Prado.WebUI.TTimeTriggeredCallback.timers[timer.options.ID] = timer;
	},

	start(id) {
		if(Prado.WebUI.TTimeTriggeredCallback.timers[id])
			Prado.WebUI.TTimeTriggeredCallback.timers[id].startTimer();
	},

	stop(id) {
		if(Prado.WebUI.TTimeTriggeredCallback.timers[id])
			Prado.WebUI.TTimeTriggeredCallback.timers[id].stopTimer();
	},

	setTimerInterval(id, value) {
		if(Prado.WebUI.TTimeTriggeredCallback.timers[id])
			Prado.WebUI.TTimeTriggeredCallback.timers[id].setTimerInterval(value);
	}
});

Prado.WebUI.ActiveListControl = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		if(this.element)
		{
			this.options = options;
			this.observe(this.element, "change", this.doCallback.bind(this));
		}
	},

	doCallback(event) {
		const request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.dispatch();
		event.preventDefault();
	}
});

Prado.WebUI.TActiveDropDownList = Prado.Class(Prado.WebUI.ActiveListControl);
Prado.WebUI.TActiveListBox = Prado.Class(Prado.WebUI.ActiveListControl);

/**
 * Observe event of a particular control to trigger a callback request.
 */
Prado.WebUI.TEventTriggeredCallback = Prado.Class(Prado.WebUI.Control,
{
	onInit(options) {
		this.options = options || {} ;
		const element = document.getElementById(options['ControlID']);
		if(element)
			this.observe(element, this.getEventName(element), this.doCallback.bind(this));
	},

	getEventName(element) {
		const name = this.options.EventName;
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

	doCallback(event) {
		const request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		request.dispatch();
		if(this.options.StopEvent == true)
			event.preventDefault();
	}
});

/**
 * Observe changes to a property of a particular control to trigger a callback.
 */
Prado.WebUI.TValueTriggeredCallback = Prado.Class(Prado.WebUI.Control,
{
	count : 1,

	observing : true,

	onInit(options) {
		this.options = options || {} ;
		this.options.PropertyName = this.options.PropertyName || 'value';
		const element = document.getElementById(options['ControlID']);
		this.value = element ? element[this.options.PropertyName] : undefined;
		Prado.WebUI.TValueTriggeredCallback.register(this);
		this.startObserving();
	},

	stopObserving() {
		this.clearTimeout(this.timer);
		this.observing = false;
	},

	startObserving() {
		this.timer = this.setTimeout(this.checkChanges.bind(this), this.options.Interval*1000);
	},

	checkChanges() {
		const element = document.getElementById(this.options.ControlID);
		if(element)
		{
			const value = element[this.options.PropertyName];
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

	doCallback(oldValue, newValue) {
		const request = new Prado.CallbackRequest(this.options.EventTarget, this.options);
		const param = {'OldValue' : oldValue, 'NewValue' : newValue};
		request.setCallbackParameter(param);
		request.dispatch();
	},

	onDone() {
		if (this.observing)
			this.stopObserving();
	}
});

Object.assign(Prado.WebUI.TValueTriggeredCallback,
{
	//class methods

	timers : {},

	register(timer) {
		Prado.WebUI.TValueTriggeredCallback.timers[timer.options.ID] = timer;
	},

	stop(id) {
		Prado.WebUI.TValueTriggeredCallback.timers[id].stopObserving();
	}
});

Prado.WebUI.TActiveTableCell = Prado.Class(Prado.WebUI.CallbackControl);
Prado.WebUI.TActiveTableRow = Prado.Class(Prado.WebUI.CallbackControl);
