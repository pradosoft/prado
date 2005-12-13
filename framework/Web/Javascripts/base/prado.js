Prado = Class.create();

Prado.version = '3.0a';

Prado.Button = Class.create();

Prado.Button.buttonFired = false;
Prado.Button.fireButton = function(event, target)
{
	if (!Prado.Button.buttonFired && event.keyCode == 13 && !(event.srcElement && (event.srcElement.tagName.toLowerCase() == "textarea")))
	{
		var defaultButton = document.getElementById ? document.getElementById(target) : document.all[target];
		if (defaultButton && typeof(defaultButton.click) != "undefined")
		{
			Prado.Button.buttonFired = true;
			defaultButton.click();
			event.cancelBubble = true;
			if (event.stopPropagation)
				event.stopPropagation();
			return false;
		}
	}
	return true;
}

Prado.TextBox = Class.create();

/**
 * Returns FALSE when the "Enter" key is pressed AND when onchange
 * property is defined. The onchange function is called. However, 
 * it does not call event listener functions.
 * @return boolean false if "Enter" and onchange property is defined, true otherwise.
 */
Prado.TextBox.handleReturnKey = function(ev)
{
	var kc = ev.keyCode != null ? ev.keyCode : ev.charCode;
	if(kc == Event.KEY_RETURN)
	{
		var target = Event.element(ev);
		if(target && isFunction(target.onchange))
		{
			target.onchange();
			Event.stop(ev);
			return false;
		}
	}
	return true;
}

/**
 * Creates a LinkButton and register the post back to the onclick event.
 */
/* to finish when doPostback changes
Prado.LinkButton = Class.create();
Prado.LinkButton.prototype =
{
	initialize : function(element, name)
	{
		Event.observe(element, 'click', function(e)
		{
			Prado.doPostback(element, name, '');
			Event.stop(e);
		});
	}
}*/