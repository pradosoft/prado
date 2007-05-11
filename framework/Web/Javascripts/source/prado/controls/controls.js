Prado.WebUI = Class.create();

Prado.WebUI.PostBackControl = Class.create();

Prado.WebUI.PostBackControl.prototype =
{
	initialize : function(options)
	{
		this._elementOnClick = null, //capture the element's onclick function

		this.element = $(options.ID);
		if(this.element)
		{
			if(this.onInit)
				this.onInit(options);
		}
	},

	onInit : function(options)
	{
		if(typeof(this.element.onclick)=="function")
		{
			this._elementOnClick = this.element.onclick.bind(this.element);
			this.element.onclick = null;
		}
		Event.observe(this.element, "click", this.elementClicked.bindEvent(this,options));
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
		if(doPostBack && !src.attributes['disabled'])
			this.onPostBack(event,options);
		if(typeof(onclicked) == "boolean" && !onclicked)
			Event.stop(event);
	},

	onPostBack : function(event, options)
	{
		Prado.PostBack(event,options);
	}
};

Prado.WebUI.TButton = Class.extend(Prado.WebUI.PostBackControl);
Prado.WebUI.TLinkButton = Class.extend(Prado.WebUI.PostBackControl);
Prado.WebUI.TCheckBox = Class.extend(Prado.WebUI.PostBackControl);
Prado.WebUI.TBulletedList = Class.extend(Prado.WebUI.PostBackControl);
Prado.WebUI.TImageMap = Class.extend(Prado.WebUI.PostBackControl);

/**
 * TImageButton client-side behaviour. With validation, Firefox needs
 * to capture the x,y point of the clicked image in hidden form fields.
 */
Prado.WebUI.TImageButton = Class.extend(Prado.WebUI.PostBackControl);
Object.extend(Prado.WebUI.TImageButton.prototype,
{
	/**
	 * Override parent onPostBack function, tried to add hidden forms
	 * inputs to capture x,y clicked point.
	 */
	onPostBack : function(event, options)
	{
		if(!this.hasXYInput)
		{
			this.addXYInput(event,options);
			this.hasXYInput = true;
		}
		Prado.PostBack(event, options);
	},

	/**
	 * Add hidden inputs to capture the x,y point clicked on the image.
	 * @param event DOM click event.
	 * @param array image button options.
	 */
	addXYInput : function(event,options)
	{
		imagePos = Position.cumulativeOffset(this.element);
		clickedPos = [event.clientX, event.clientY];
		x = clickedPos[0]-imagePos[0]+1;
		y = clickedPos[1]-imagePos[1]+1;
		x = x < 0 ? 0 : x;
		y = y < 0 ? 0 : y;
		id = options['EventTarget'];
		x_input = $(id+"_x");
		y_input = $(id+"_y");
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
	}
});


/**
 * Radio button, only initialize if not already checked.
 */
Prado.WebUI.TRadioButton = Class.extend(Prado.WebUI.PostBackControl);
Prado.WebUI.TRadioButton.prototype.onRadioButtonInitialize = Prado.WebUI.TRadioButton.prototype.initialize;
Object.extend(Prado.WebUI.TRadioButton.prototype,
{
	initialize : function(options)
	{
		this.element = $(options['ID']);
		if(this.element)
		{
			if(!this.element.checked)
				this.onRadioButtonInitialize(options);
		}
	}
});


Prado.WebUI.TTextBox = Class.extend(Prado.WebUI.PostBackControl,
{
	onInit : function(options)
	{
		this.options=options;
		if(options['TextMode'] != 'MultiLine')
			Event.observe(this.element, "keydown", this.handleReturnKey.bind(this));
		if(this.options['AutoPostBack']==true)
			Event.observe(this.element, "change", Prado.PostBack.bindEvent(this,options));
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

Prado.WebUI.TListControl = Class.extend(Prado.WebUI.PostBackControl,
{
	onInit : function(options)
	{
		Event.observe(this.element, "change", Prado.PostBack.bindEvent(this,options));
	}
});

Prado.WebUI.TListBox = Class.extend(Prado.WebUI.TListControl);
Prado.WebUI.TDropDownList = Class.extend(Prado.WebUI.TListControl);

Prado.WebUI.DefaultButton = Class.create();
Prado.WebUI.DefaultButton.prototype =
{
	initialize : function(options)
	{
		this.options = options;
		this._event = this.triggerEvent.bindEvent(this);
		Event.observe(options['Panel'], 'keydown', this._event);
	},

	triggerEvent : function(ev, target)
	{
		var enterPressed = Event.keyCode(ev) == Event.KEY_RETURN;
		var isTextArea = Event.element(ev).tagName.toLowerCase() == "textarea";
		if(enterPressed && !isTextArea)
		{
			var defaultButton = $(this.options['Target']);
			if(defaultButton)
			{
				this.triggered = true;
				$('PRADO_POSTBACK_TARGET').value = this.options.EventTarget;
				Event.fireEvent(defaultButton, this.options['Event']);
				Event.stop(ev);
			}
		}
	}
};

Prado.WebUI.TTextHighlighter=Class.create();
Prado.WebUI.TTextHighlighter.prototype=
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