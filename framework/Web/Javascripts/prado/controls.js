

Prado.Button = Class.create();

/**
 * Usage: Event.observe("panelID", "keypress", Prado.fireButton.bindEvent($("panelID"), "targetButtonID"));
 */
Object.extend(Prado.Button,
{
	buttonFired : false,
	fireButton : function(e, target)
	{
		var eventFired = !this.buttonFired && Event.keyCode(e) == Event.KEY_RETURN;
		var isTextArea = Event.element(e).targName.toLowerCase() == "textarea";
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

/**
 * Usage: Event.observe("textboxID", "keypress", Prado.fireButton.bindEvent($("textboxID")));
 */
Object.extend(Prado.TextBox,
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
});
