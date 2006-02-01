Prado.WebUI = Class.create();

//base postback-able controls
Prado.WebUI.PostBackControl = Class.create();
Object.extend(Prado.WebUI.PostBackControl.prototype,
{
	initialize : function(options)
	{
		this.element = $(options['ID']);
		if(options['CausesValidation'] && Prado.Validation)
			Prado.Validation.AddTarget(options['ID'], options['ValidationGroup']);

		//TODO: what do the following options do?
		//options['PostBackUrl']
		//options['ClientSubmit']

		if(this.onInit)
			this.onInit(options);
	}
});

//short cut to create postback components
Prado.WebUI.createPostBackComponent = function(definition)
{
	var component = Class.create();
	Object.extend(component.prototype, Prado.WebUI.PostBackControl.prototype);
	if(definition) Object.extend(component.prototype, definition);
	return component;
}

Prado.WebUI.TButton = Prado.WebUI.createPostBackComponent();

Prado.WebUI.ClickableComponent = Prado.WebUI.createPostBackComponent(
{
	onInit : function(options)
	{
		Event.observe(this.element, "click", Prado.PostBack.bindEvent(this,options));
	}
});

Prado.WebUI.TLinkButton = Prado.WebUI.ClickableComponent;
Prado.WebUI.TImageButton = Prado.WebUI.ClickableComponent;
Prado.WebUI.TCheckBox = Prado.WebUI.ClickableComponent;
Prado.WebUI.TRadioButton = Prado.WebUI.ClickableComponent;
Prado.WebUI.TBulletedList = Prado.WebUI.ClickableComponent;

Prado.WebUI.TTextBox = Prado.WebUI.createPostBackComponent(
{
	onInit : function(options)
	{
		if(options['TextMode'] != 'MultiLine')
			Event.observe(this.element, "down", this.handleReturnKey.bind(this));
		Event.observe(this.element, "change", Prado.PostBack.bindEvent(this,options));
	},

	handleReturnKey : function(e)
	{
		 if(Event.keyCode(e) == Event.KEY_RETURN)
        {
			var target = Event.element(e);
			if(target)
			{
				Event.fireEvent(target, "change");
				Event.stop(e);
			}
		}
	}
});

Prado.WebUI.TListControl = Prado.WebUI.createPostBackComponent(
{
	onInit : function(options)
	{
		Event.observe(this.element, "change", Prado.PostBack.bindEvent(this,options));
	}
});

Prado.WebUI.TListBox = Prado.WebUI.TListControl;
Prado.WebUI.TDropDownList = Prado.WebUI.TListControl;

Prado.WebUI.DefaultButton = Class.create();
Object.extend(Prado.WebUI.DefaultButton.prototype,
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
				Event.fireEvent(defaultButton, this.options['Event']);
				Event.stop(ev);
			}
		}
	}
});

Prado.WebUI.TTextHighlighter=Class.create();
Prado.WebUI.TTextHighlighter.prototype={initialize:function(id)
{
	if(!window.clipboardData) return;
	var options = 
	{
		href : 'javascript:;//copy code to clipboard',
		onclick : 'Prado.WebUI.TTextHighlighter.copy(this)',
		onmouseover : 'Prado.WebUI.TTextHighlighter.hover(this)',
		onmouseout : 'Prado.WebUI.TTextHighlighter.out(this)'
	}
	var div = DIV({className:'copycode'}, A(options, 'Copy Code'));
	document.write(DIV(null,div).innerHTML);
}};

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
