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
Prado.WebUI.TCheckBox = Prado.WebUI.ClickableComponent;
Prado.WebUI.TRadioButton = Prado.WebUI.ClickableComponent;
Prado.WebUI.TBulletedList = Prado.WebUI.ClickableComponent;

Prado.WebUI.TTextBox = Prado.WebUI.createPostBackComponent(
{
	onInit : function(options)
	{
		if(options['TextMode'] != 'MultiLine')
			Event.observe(this.element, "keypress", this.handleReturnKey.bind(this));
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
				return false;
			}
		}
		return true;
	}
});

Prado.WebUI.TListControl = Prado.WebUI.createPostBackComponent(
{
	onInit : function(options)
	{
		Event.observe(this.element.id, "change", Prado.PostBack.bindEvent(this,options));
	}
});

Prado.WebUI.TListBox = Prado.WebUI.TListControl;
Prado.WebUI.TDropDownList = Prado.WebUI.TListControl;


//Prado.Button = Class.create();

/**
 * Usage: Event.observe("panelID", "keypress", Prado.fireButton.bindEvent($("panelID"), "targetButtonID"));
 */
/*Object.extend(Prado.Button,
{
	buttonFired : false,
	fireButton : function(e, target)
	{
		var eventFired = !this.buttonFired && Event.keyCode(e) == Event.KEY_RETURN;
		var isTextArea = Event.element(e).tagName.toLowerCase() == "textarea";
		if (eventFired && !isTextArea)
        {
			var defaultButton = $(target);
			if (defaultButton)
			{
				Prado.Button.buttonFired = true;
				Event.fireEvent(defaultButton,"click");
				Event.stop(e);
				return false;
			}
        }
        return true;
	}
});

Prado.TextBox = Class.create();
*/
/**
 * Usage: Event.observe("textboxID", "keypress", Prado.fireButton.bindEvent($("textboxID")));
 */
/*Object.extend(Prado.TextBox,
{
	handleReturnKey : function(e)
	{
        if(Event.keyCode(e) == Event.KEY_RETURN)
        {
			var target = Event.element(e);
			if(target)
			{
				Event.fireEvent(target, "change");
				Event.stop(e);
				return false;
			}
		}
		return true;
	}
});*/
