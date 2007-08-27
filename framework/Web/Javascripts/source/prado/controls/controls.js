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
		if(doPostBack && !Prado.Element.isDisabled(src))
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

Prado.WebUI.TTabPanel = Class.create();
Prado.WebUI.TTabPanel.prototype =
{
	initialize : function(options)
	{
		this.element = $(options.ID);
		this.onInit(options);
	},

	onInit : function(options)
	{
		this.views = options.Views;
		this.hiddenField = $(options.ID+'_1');
		this.activeCssClass = options.ActiveCssClass;
		this.normalCssClass = options.NormalCssClass;
		var length = options.Views.length;
		for(var i = 0; i<length; i++)
		{
			var item = options.Views[i];
			var element = $(item+'_0');
			if (element)
			{
				Event.observe(element, "click", this.elementClicked.bindEvent(this,item));
			}
		}
	},

	elementClicked : function(event,viewID)
	{
		var length = this.views.length;
		for(var i = 0; i<length; i++)
		{
			var item = this.views[i];
			if ($(item))
			{
				if(item == viewID)
				{
					$(item+'_0').className=this.activeCssClass;
					$(item).show();
					this.hiddenField.value=i;
				}
				else
				{
					$(item+'_0').className=this.normalCssClass;
					$(item).hide();
				}
			}
		}
	}
};


Prado.WebUI.TKeyboard = Class.create();
Prado.WebUI.TKeyboard.prototype =
{
	initialize : function(options)
	{
		this.element = $(options.ID);
		this.onInit(options);
	},

	onInit : function(options)
    {
		this.cssClass = options['CssClass'];
        this.forControl = document.getElementById(options['ForControl']);
        this.autoHide = options['AutoHide'];

        this.flagShift = false;
        this.flagCaps = false;
        this.flagHover = false;
        this.flagFocus = false;

        this.keys = new Array
        (
            new Array('` ~ D', '1 ! D', '2 @ D', '3 # D', '4 $ D', '5 % D', '6 ^ D', '7 &amp; D', '8 * D', '9 ( D', '0 ) D', '- _ D', '= + D', 'Bksp Bksp Bksp'),
            new Array('Del Del Del', 'q Q L', 'w W L', 'e E L', 'r R L', 't T L', 'y Y L', 'u U L', 'i I L', 'o O L', 'p P L', '[ { D', '] } D', '\\ | \\'),
            new Array('Caps Caps Caps', 'a A L', 's S L', 'd D L', 'f F L', 'g G L', 'h H L', 'j J L', 'k K L', 'l L L', '; : D', '\' " D', 'Exit Exit Exit'),
            new Array('Shift Shift Shift', 'z Z L', 'x X L', 'c C L', 'v V L', 'b B L', 'n N L', 'm M L', ', &lt; D', '. &gt; D', '/ ? D', 'Shift Shift Shift')
        );

        if (this.isObject(this.forControl))
        {
            this.forControl.keyboard = this;
            this.forControl.onfocus = function() {this.keyboard.show(); };
            this.forControl.onblur = function() {if (this.keyboard.flagHover == false) this.keyboard.hide();};
            this.forControl.onkeydown = function(e) {if (!e) e = window.event; var key = (e.keyCode)?e.keyCode:e.which; if(key == 9)  this.keyboard.hide();;};
            this.forControl.onselect = this.saveSelection;
            this.forControl.onclick = this.saveSelection;
            this.forControl.onkeyup = this.saveSelection;
        }

        this.render();

        this.tagKeyboard.onmouseover = function() {this.keyboard.flagHover = true;};
        this.tagKeyboard.onmouseout = function() {this.keyboard.flagHover = false;};

        if (!this.autoHide) this.show();
    },

	isObject : function(a)
	{
		return (typeof a == 'object' && !!a) || typeof a == 'function';
	},

	createElement : function(tagName, attributes, parent)
    {
        var tagElement = document.createElement(tagName);
        if (this.isObject(attributes)) for (attribute in attributes) tagElement[attribute] = attributes[attribute];
        if (this.isObject(parent)) parent.appendChild(tagElement);
        return tagElement;
    },

	onmouseover : function()
	{
		this.className += ' Hover';
	},

	onmouseout : function()
	{
		this.className = this.className.replace(/( Hover| Active)/ig, '');
	},

    onmousedown : function()
    {
    	this.className += ' Active';
	},

    onmouseup : function()
    {
    	this.className = this.className.replace(/( Active)/ig, '');
    	this.keyboard.type(this.innerHTML);
	},

	render : function()
    {
        this.tagKeyboard = this.createElement('div', {className: this.cssClass, onselectstart: function() {return false;}}, this.element);
        this.tagKeyboard.keyboard = this;

        for (var line = 0; line < this.keys.length; line++)
        {
            var tagLine = this.createElement('div', {className: 'Line'}, this.tagKeyboard);
            for (var key = 0; key < this.keys[line].length; key++)
            {
                var split = this.keys[line][key].split(' ');
                var tagKey = this.createElement('div', {className: 'Key ' + split[2]}, tagLine);
                var tagKey1 = this.createElement('div', {className: 'Key1', innerHTML: split[0], keyboard: this, onmouseover: this.onmouseover, onmouseout: this.onmouseout, onmousedown: this.onmousedown, onmouseup: this.onmouseup}, tagKey);
                var tagKey2 = this.createElement('div', {className: 'Key2', innerHTML: split[1], keyboard: this, onmouseover: this.onmouseover, onmouseout: this.onmouseout, onmousedown: this.onmousedown, onmouseup: this.onmouseup}, tagKey);
            }
        }
    },

    isShown : function()
    {
        return (this.tagKeyboard.style.visibility.toLowerCase() == 'visible');
    },

    show : function()
    {
        if (this.isShown() == false) this.tagKeyboard.style.visibility = 'visible';
    },

    hide : function()
    {
        if (this.isShown() == true && this.autoHide) {this.tagKeyboard.style.visibility = 'hidden'; }
    },

    type : function(key)
    {
        var input = this.forControl;
        var command = key.toLowerCase();

        if (command == 'exit') {this.hide();}
        else if (input != 'undefined' && input != null && command == 'bksp') {this.insert(input, 'bksp');}
        else if (input != 'undefined' && input != null && command == 'del') {this.insert(input, 'del');}
        else if (command == 'shift') {this.tagKeyboard.className = this.flagShift?'Keyboard Off':'Keyboard Shift';this.flagShift = this.flagShift?false:true;}
        else if (command == 'caps') {this.tagKeyboard.className = this.caps?'Keyboard Off':'Keyboard Caps';this.caps = this.caps?false:true;}
        else if (input != 'undefined' && input != null)
        {
            if (this.flagShift == true) {this.flagShift = false; this.tagKeyboard.className = 'Keyboard Off';}
            key = key.replace(/&gt;/, '>'); key = key.replace(/&lt;/, '<'); key = key.replace(/&amp;/, '&');
            this.insert(input, key);
        }

        if (command != 'exit') input.focus();
    },

    saveSelection : function()
    {
        if (this.keyboard.forControl.createTextRange)
        {
            this.keyboard.selection = document.selection.createRange().duplicate();
            return;
        }
    },

    insert : function(field, value)
    {
        if (this.forControl.createTextRange && this.selection)
        {
            if (value == 'bksp') {this.selection.moveStart("character", -1); this.selection.text = '';}
            else if (value == 'del') {this.selection.moveEnd("character", 1); this.selection.text = '';}
            else {this.selection.text = value;}
            this.selection.select();
        }
        else
        {
            var selectStart = this.forControl.selectionStart;
            var selectEnd = this.forControl.selectionEnd;
            var start = (this.forControl.value).substring(0, selectStart);
            var end = (this.forControl.value).substring(selectEnd, this.forControl.textLength);

            if (value == 'bksp') {start = start.substring(0, start.length - 1); selectStart -= 1; value = '';}
            if (value == 'del') {end = end.substring(1, end.length); value = '';}

            this.forControl.value = start + value + end;
            this.forControl.selectionStart = selectEnd + value.length;
            this.forControl.selectionEnd = selectStart + value.length;
        }
    }
}